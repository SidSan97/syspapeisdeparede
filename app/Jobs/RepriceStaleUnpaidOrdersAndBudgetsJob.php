<?php

namespace App\Jobs;

use App\Models\Budget;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class RepriceStaleUnpaidOrdersAndBudgetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public float $newPriceVista,
        public float $newPricePrazo,
        public float $oldPriceVista,
        public float $oldPricePrazo
    ) {}

    public function handle(): void
    {
        $cutoff = Carbon::now()->subDays(30);

        Order::query()
            ->where('paid', 0)
            ->where('updated_at', '<=', $cutoff)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'Cancelado');
            })
            ->with(['rooms.walls.collectionModel'])
            ->chunkById(100, function ($orders) {
                foreach ($orders as $order) {
                    $modelCost = $this->calculateModelCost($order->rooms);
                    $freight = (float) ($order->selected_carrier_price ?? 0);
                    $area = (float) ($order->total_area ?? 0);

                    $order->update([
                        'total_amount' => round(($area * $this->newPriceVista) + $modelCost + $freight, 2),
                        'total_amount_installments' => round(($area * $this->newPricePrazo) + $modelCost + $freight, 2),
                    ]);
                }
            });

        Budget::query()
            ->whereHas('rooms.order', function ($query) use ($cutoff) {
                $query->where('paid', 0)
                    ->where('updated_at', '<=', $cutoff)
                    ->where(function ($subQuery) {
                        $subQuery->whereNull('status')
                            ->orWhere('status', '!=', 'Cancelado');
                    });
            })
            ->with(['rooms.walls.collectionModel'])
            ->chunkById(100, function ($budgets) {
                foreach ($budgets as $budget) {
                    $modelCost = $this->calculateModelCost($budget->rooms);
                    $freight = (float) ($budget->selected_carrier_price ?? 0);
                    $area = (float) ($budget->total_area ?? 0);

                    $budget->update([
                        'total_amount' => round(($area * $this->newPriceVista) + $modelCost + $freight, 2),
                        'total_amount_installments' => round(($area * $this->newPricePrazo) + $modelCost + $freight, 2),
                    ]);
                }
            });

        Setting::set('tiny_erp_last_processed_price_payment', (string) $this->newPriceVista, 'string');
        Setting::set('tiny_erp_last_processed_price_installment', (string) $this->newPricePrazo, 'string');
        Setting::set('tiny_erp_last_reprice_at', Carbon::now()->toDateTimeString(), 'string');
    }

    private function calculateModelCost(iterable $rooms): float
    {
        $total = 0.0;

        foreach ($rooms as $room) {
            foreach ($room->walls ?? [] as $wall) {
                if ($wall->collectionModel !== null) {
                    $total += (float) ($wall->collectionModel->value ?? 0);
                }
            }
        }

        return round($total, 2);
    }
}


<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Repositories\DropshippingRepository;
use App\Repositories\OrderBudgetRepository;
use App\Services\OrderService;
use App\Support\Budget\BudgetCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MergeOrderAction
{
    public function __construct(
        protected OrderService $orderService,
        protected OrderBudgetRepository $orderBudgetRepository,
        protected DropshippingRepository $dropshippingRepository,
    ) {}

    public function execute(array $ids, string $name): Order
    {
        $ids = $this->orderService->prepareMergeOrderIds($ids);

        if (count($ids) < 2) {
            throw new \InvalidArgumentException('Selecione pelo menos dois pedidos.');
        }

        $orders = Order::query()
            ->whereIn('id', $ids)
            ->with(['rooms.walls', 'dropshippingData'])
            ->orderBy('id')
            ->get();

        if ($orders->count() !== count($ids)) {
            throw new \InvalidArgumentException('Um ou mais pedidos não foram encontrados.');
        }

        $base = $orders->first();
        $allDs = $this->orderService->validateOrdersForMerge($orders);
        $mergedRooms = $this->orderService->collectMergedRoomsFromOrders($orders);

        $selectedCarrier = [
            'price' => (float) ($base->selected_carrier_price ?? 0),
            'deliveryTime' => (int) ($base->selected_carrier_delivery_time ?? 0),
        ];
        $totals = $this->orderService->computeMergedOrderTotals($mergedRooms, $selectedCarrier);

        return DB::transaction(function () use (
            $base,
            $name,
            $mergedRooms,
            $totals,
            $allDs,
            $ids
        ) {
            $order = Order::create([
                'user_id' => $base->user_id,
                'tenant_id' => $base->tenant_id,
                'name' => $name,
                'total_area' => $totals['total_area'],
                'total_amount' => $totals['total_amount'],
                'total_amount_installments' => $totals['total_amount_installments'],
                'total_amount_markup' => null,
                'total_amount_installments_markup' => null,
                'delivery_time' => $totals['delivery_time'],
                'payment_method' => $base->payment_method,
                'installments' => $base->installments,
                'cep' => $base->cep,
                'selected_carrier_name' => $base->selected_carrier_name,
                'selected_carrier_price' => $base->selected_carrier_price,
                'selected_carrier_delivery_time' => $base->selected_carrier_delivery_time,
                'carriers_snapshot' => $base->carriers_snapshot,
                'status' => 'Em aberto',
                'dropshipping_budget' => $allDs ? 1 : 0,
                'paid' => 0,
                'payment_status' => 'unpaid',
                'nf_sent' => 0,
            ]);

            $tenantId = $order->tenant_id;

            foreach ($mergedRooms as $roomIndex => $roomData) {
                $room = $order->rooms()->create([
                    'tenant_id' => $tenantId,
                    'budget_id' => null,
                    'name' => $roomData['name'] ?? null,
                    'position' => $roomIndex,
                    'raw_payload' => $roomData,
                ]);

                $wallsSequence = BudgetCalculator::calculateWallsSequence($roomData['walls'] ?? []);

                foreach (($roomData['walls'] ?? []) as $wallIndex => $wallData) {
                    $wallMetrics = $wallsSequence['perWall'][$wallIndex] ?? null;
                    $totalAreaWall = (float) ($wallMetrics['total_area'] ?? 0);
                    $stripCount = (int) ($wallMetrics['strip_count'] ?? 0);
                    $stripHeight = $wallMetrics['strip_height'] ?? null;

                    $room->walls()->create([
                        'tenant_id' => $tenantId,
                        'name' => $wallData['name'] ?? null,
                        'position' => $wallIndex,
                        'width' => $wallData['width'] ?? null,
                        'height' => $wallData['height'] ?? null,
                        'continue_same_art' => (bool) ($wallData['continueSameArt'] ?? false),
                        'continuations' => $wallData['continuations'] ?? [],
                        'collection_model_id' => $wallData['model'] ?? null,
                        'total_area' => $totalAreaWall,
                        'strip_height' => $stripHeight,
                        'strip_count' => $stripCount,
                        'comment_referring_model' => $wallData['comment_referring_model'] ?? null,
                        'link_referring_model' => $wallData['link_referring_model'] ?? null,
                        'files_referring_model' => isset($wallData['files_referring_model'])
                            ? (array) $wallData['files_referring_model']
                            : null,
                        'collection_referring_model' => $wallData['collection_referring_model'] ?? null,
                    ]);
                }
            }

            $primaryRoomId = $order->rooms()->orderBy('position')->value('id');
            if ($primaryRoomId) {
                $order->update(['primary_budget_room_id' => $primaryRoomId]);
            }

            if ($allDs && $base->dropshippingData) {
                $ds = $base->dropshippingData->toArray();
                unset($ds['id'], $ds['budget_id'], $ds['order_id'], $ds['dealer_id'], $ds['created_at'], $ds['updated_at']);
                $this->dropshippingRepository->create(
                    $ds,
                    null,
                    $order->id,
                    (int) Auth::id()
                );
            }

            $this->orderBudgetRepository->syncFromOrderRooms($order->fresh(['rooms.walls']));

            Order::query()->whereIn('id', $ids)->update(['status' => 'Cancelado']);

            return $order->fresh(['user', 'tenant', 'primaryRoom', 'rooms.walls.collectionModel', 'dropshippingData']);
        });
    }
}

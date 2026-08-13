<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderChangeHistory;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\Auth;

class OrderChangeHistoryService
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function logApprovedOrderEdit(Order $order, array $before, array $after, ?User $user = null): ?OrderChangeHistory
    {
        if (! OrderStatus::is($before['status'] ?? $order->status, OrderStatus::APPROVED)) {
            return null;
        }

        $user ??= Auth::user();
        $userName = $user?->name ?? 'Usuário';

        $changedFields = $this->diffRelevantFields($before, $after);

        $order->loadMissing('tenant');

        return OrderChangeHistory::query()->create([
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'reseller_id' => $order->tenant?->reseller_id,
            'user_id' => $user?->id,
            'description' => "{$userName} editou o pedido aprovado.",
            'changes' => [
                'before' => $before,
                'after' => $after,
                'changed_fields' => $changedFields,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Order $order): array
    {
        return [
            'name' => $order->name,
            'status' => $order->status,
            'total_area' => (float) ($order->total_area ?? 0),
            'total_amount' => (float) ($order->total_amount ?? 0),
            'total_amount_installments' => (float) ($order->total_amount_installments ?? 0),
            'delivery_time' => (int) ($order->delivery_time ?? 0),
            'selected_carrier_name' => $order->selected_carrier_name,
            'selected_carrier_price' => (float) ($order->selected_carrier_price ?? 0),
            'observation' => $order->observation,
            'rooms_count' => $order->rooms()->count(),
            'walls_count' => $order->rooms()->withCount('walls')->get()->sum('walls_count'),
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<string>
     */
    protected function diffRelevantFields(array $before, array $after): array
    {
        $changed = [];

        foreach ($after as $key => $value) {
            $previous = $before[$key] ?? null;

            if (is_numeric($previous) && is_numeric($value)) {
                if ((float) $previous !== (float) $value) {
                    $changed[] = $key;
                }

                continue;
            }

            if ($previous != $value) {
                $changed[] = $key;
            }
        }

        return $changed;
    }
}

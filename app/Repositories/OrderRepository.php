<?php

namespace App\Repositories;

use App\Models\Budget;
use App\Models\Order;
use App\Models\OrderBudget;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class OrderRepository
{

    public function all(): Collection
    {
        $user = Auth::user();

        $query = Order::with(['rooms.walls.collectionModel', 'user', 'tenant', 'primaryRoom'])
            ->orderByDesc('created_at');

        if (!$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('tenant_id', $user->id);
            });
        }

        return $query->get();
    }

    public function getLayoutsForApprove()
    {
        return OrderBudget::whereIn('status', ['Aprovar Layout', 'Pendente de Revisão'])
            ->whereNotNull('budget_wall_id')
            ->with([
                'order' => function ($query) {
                    $query->with([
                        'rooms.walls.collectionModel.files',
                        'user'
                    ]);
                },
                'wall' => function ($query) {
                    $query->with([
                        'collectionModel.files',
                        'room'
                    ]);
                },
                'layoutColumnName',
                'users', // Carrega os membros do card (busca na layout_card_user por card_id e pega os dados do usuário)
                'history' // Carrega o histórico do card
            ])
            ->get();
    }

    public function getLayoutsForProduction()
    {
        return OrderBudget::whereNotNull('budget_wall_id')
            ->whereHas('order', function ($query) {
                $query->where('paid', 1);
            })
            ->with([
                'order' => function ($query) {
                    $query->with([
                        'rooms.walls.collectionModel.files',
                        'user'
                    ]);
                },
                'wall' => function ($query) {
                    $query->with([
                        'collectionModel.files',
                        'room'
                    ]);
                },
                'layoutColumnName',
                'users', // Carrega os membros do card (busca na layout_card_user por card_id e pega os dados do usuário)
                'history' // Carrega o histórico do card
            ])
            ->get();
    }

    public function find(int $id): ?Order
    {
        $user = Auth::user();

        $query = Order::with(['rooms.walls.collectionModel', 'user', 'tenant', 'primaryRoom']);

        if (!$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('tenant_id', $user->id);
            });
        }

        return $query->find($id);
    }

    public function create(array $data): Order
    {
        $user = Auth::user();
        $tenantId = $user->isTenant() ? $user->id : null;

        $orderData = array_merge($data, [
            'user_id' => $data['user_id'] ?? $user->id,
            'tenant_id' => $data['tenant_id'] ?? $tenantId,
        ]);

        return Order::create($orderData);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order->fresh(['user', 'tenant', 'primaryRoom']);
    }

    public function delete(Order $order): bool
    {
        return $order->delete();
    }

    public function getByStatus(string $status): Collection
    {
        $user = Auth::user();

        $query = Order::with(['user', 'tenant', 'primaryRoom'])
            ->where('status', $status);

        if (!$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('tenant_id', $user->id);
            });
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function createFromBudget(Budget $budget, array $additionalData = []): Order
    {
        $orderData = [
            'user_id' => $budget->user_id,
            'tenant_id' => $budget->tenant_id,
            'name' => $budget->name,
            'total_area' => $budget->total_area,
            'total_amount' => $budget->total_amount,
            'total_amount_installments' => $budget->total_amount_installments,
            'delivery_time' => $budget->delivery_time,
            'payment_method' => $budget->payment_method,
            'installment_limit' => $budget->installment_limit,
            'installments' => $budget->installments,
            'cep' => $budget->cep,
            'selected_carrier_name' => $budget->selected_carrier_name,
            'selected_carrier_price' => $budget->selected_carrier_price,
            'selected_carrier_delivery_time' => $budget->selected_carrier_delivery_time,
            'carriers_snapshot' => $budget->carriers_snapshot,
            'primary_budget_room_id' => $budget->primary_budget_room_id,
            'status' => $additionalData['status'] ?? $budget->status ?? 'Pendente de Revisão',
            'payment_file' => $budget->payment_file,
            'comment_referring_model' => $additionalData['comment_referring_model'] ?? $budget->comment_referring_model,
            'link_referring_model' => $additionalData['link_referring_model'] ?? $budget->link_referring_model,
            'files_referring_model' => $additionalData['files_referring_model'] ?? $budget->files_referring_model,
            'collection_referring_model' => $additionalData['collection_referring_model'] ?? $budget->collection_referring_model,
            'dropshipping_budget' => $budget->dropshipping_budget,
        ];

        return $this->create($orderData);
    }
}


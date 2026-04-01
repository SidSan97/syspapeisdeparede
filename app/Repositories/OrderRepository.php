<?php

namespace App\Repositories;

use App\Models\Budget;
use App\Models\Order;
use App\Models\OrderBudget;
use App\Support\Budget\BudgetCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderRepository
{

    public function all(): Collection
    {
        $user = Auth::user();

        $query = Order::with(['rooms.walls.collectionModel', 'user', 'tenant', 'primaryRoom', 'paymentLinks'])
            ->orderByDesc('created_at');

        if (!$user->isAdmin() && !$user->isCommercial()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('tenant_id', $user->id);
            });
        }

        return $query->get();
    }

    public function paginate(array $filters = [])
    {
        $user = Auth::user();

        $query = Order::with(['user', 'tenant', 'primaryRoom', 'paymentLinks'])
            ->orderByDesc('created_at')
            ->forUser($user)
            ->search($filters['search'] ?? null)
            ->byStatus($filters['status'] ?? null)
            ->byDateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->byUserId($filters['user_id'] ?? null);

        return $query->paginate();
    }

    public function getAllById(int $id)
    {
        return Order::with('rooms.walls.collectionModel')->where('id', $id)->get();
    }

    public function getLayoutsForApprove()
    {
        return OrderBudget::whereIn('status', ['Aprovar Layout', 'Pendente de Revisão'])
            ->whereNotNull('budget_wall_id')
            ->whereHas('order', function ($query) {
                // Inclui tudo exceto pedido pago E aprovado ao mesmo tempo (pagamento parcial continua na fila)
                $query->where(function ($q) {
                    $q->where('paid', '!=', 1)
                        ->orWhere('status', '!=', 'Aprovado')
                        ->orWhereNull('status');
                });
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

    public function getLayoutsForProduction()
    {
        return OrderBudget::whereNotNull('budget_wall_id')
            ->whereHas('order', function ($query) {
                $query->where('paid', 1);
                $query->where('status', 'Aprovado');
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

        $query = Order::with(['rooms.walls.collectionModel', 'user', 'tenant', 'primaryRoom', 'paymentLinks']);

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
        if (!empty($data['rooms']) && is_array($data['rooms'])) {
            $rooms = $data['rooms'];

            // Recalcular totais quando a estrutura de ambientes/paredes muda
            $selectedCarrier = null;
            if (
                array_key_exists('selected_carrier_price', $data) &&
                $data['selected_carrier_price'] !== null
            ) {
                $selectedCarrier = [
                    'price' => (float) $data['selected_carrier_price'],
                    'deliveryTime' => (int) ($data['selected_carrier_delivery_time'] ?? 0),
                ];
            }

            $data['total_area'] = BudgetCalculator::calculateTotalArea($rooms);
            $data['total_amount'] = BudgetCalculator::calculateTotalAmountVista(
                (float) $data['total_area'],
                $rooms,
                $selectedCarrier
            );
            $data['total_amount_installments'] = BudgetCalculator::calculateTotalAmountPrazo(
                (float) $data['total_area'],
                $rooms,
                $selectedCarrier
            );
            $data['delivery_time'] = BudgetCalculator::calculateDeliveryTime($rooms, $selectedCarrier);

            DB::transaction(function () use ($order, &$data, $rooms) {
                unset($data['rooms']);
                $order->update($data);

                $tenantId = $order->tenant_id;

                // Sincronização total: remove estrutura antiga e recria conforme payload
                $order->rooms()->delete();

                foreach ($rooms as $roomIndex => $roomData) {
                    $room = $order->rooms()->create([
                        'tenant_id' => $tenantId,
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
            });

            return $order->fresh(['user', 'tenant', 'primaryRoom', 'rooms.walls.collectionModel']);
        }

        $order->update($data);

        return $order->fresh(['user', 'tenant', 'primaryRoom']);
    }

    public function delete(Order $order): bool
    {
        return $order->delete();
    }

    public function cancel(Order $order): Order
    {
        $order->update([
            'status' => 'Cancelado',
        ]);

        return $order->fresh(['rooms.walls.collectionModel', 'user', 'tenant', 'primaryRoom']);
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

    public function getReadyForInvoice(): SupportCollection
    {
        return Order::with(['orderBudgets', 'user', 'tenant'])
            ->whereHas('orderBudgets')
            //->where('nf_sent', 0)
            ->whereDoesntHave('orderBudgets', function ($query) {
                $query->where('ready_to_expedition', '!=', 1);
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_id' => $order->id,
                    'name' => $order->name,
                    'total_amount' => $order->payment_method == 'pix' ? $order->total_amount : $order->total_amount_installments,
                    'status' => $order->status,
                    'nf_sent' => $order->nf_sent,
                    'nf_id' => $order->nf_id,
                    'created_at' => $order->created_at,
                ];
            });
    }

    /**
     * Colunas compartilhadas entre budgets e orders (exceto order_id no budget).
     */
    protected function sharedAttributesFromBudget(Budget $budget): array
    {
        return [
            'user_id' => $budget->user_id,
            'tenant_id' => $budget->tenant_id,
            'name' => $budget->name,
            'total_area' => $budget->total_area,
            'total_amount' => $budget->total_amount,
            'total_amount_installments' => $budget->total_amount_installments,
            'total_amount_markup' => $budget->total_amount_markup,
            'total_amount_installments_markup' => $budget->total_amount_installments_markup,
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
            'status' => $budget->status,
            'payment_file' => $budget->payment_file,
            'dropshipping_budget' => $budget->dropshipping_budget,
        ];
    }

    public function createFromBudget(Budget $budget, array $additionalData = []): Order
    {
        $attributes = $this->sharedAttributesFromBudget($budget);
        $attributes['status'] = 'Em aberto';

        return $this->create($attributes);
    }

    public function syncFromBudget(Order $order, Budget $budget): Order
    {
        $order->update($this->sharedAttributesFromBudget($budget));

        return $order->fresh();
    }

    public function updateNfSent(int $orderId): void
    {
        Order::where('id', $orderId)->update([
            'nf_sent' => 1,
        ]);
    }

    public function updateNfId(int $orderId, string $nfId): void
    {
        Order::where('id', $orderId)->update([
            'nf_id' => $nfId,
        ]);
    }

    public function changeStatusOrder(int $orderId, string $status): void
    {
        Order::where('id', $orderId)->update([
            'status' => $status,
        ]);
    }
}


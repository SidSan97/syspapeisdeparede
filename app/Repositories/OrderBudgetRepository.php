<?php

namespace App\Repositories;

use App\Models\Budget;
use App\Models\BudgetWall;
use App\Models\LayoutColumnName;
use App\Models\Order;
use App\Models\OrderBudget;
use App\Models\User;
use App\Services\LayoutCardHistoryService;
use App\Support\OrderBudgetStatus;
use Illuminate\Support\Facades\DB;

class OrderBudgetRepository
{
    protected $orderBudget;

    protected $historyService;

    public function __construct(OrderBudget $orderBudget, LayoutCardHistoryService $historyService)
    {
        $this->orderBudget = $orderBudget;
        $this->historyService = $historyService;
    }

    public function show(int $id)
    {
        return $this->orderBudget::findOrFail($id);
    }

    public function editLayoutColumn(int $orderBudgetId, int $columnId, $user = null, ?string $typePage = null)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);

        $updateData = [];
        if ($typePage === 'product') {
            $updateData['production_column_names_id'] = $columnId;
        } else {
            $updateData['layout_column_names_id'] = $columnId;
        }

        $orderBudget->update($updateData);

        // Registrar no histórico se houver usuário
        if ($user) {
            $this->historyService->logColumnChange($orderBudgetId, $user, $columnId, $typePage);
        }

        return $orderBudget->fresh();
    }

    public function updateOrderBudgetDescription(int $orderBudgetId, string $description, $user = null, ?string $typePage = null)
    {
        $this->orderBudget::findOrFail($orderBudgetId)->update([
            'description' => $description ?? null,
        ]);

        if ($user) {
            $this->historyService->logDescriptionChange($orderBudgetId, $user, $typePage);
        }

        return $this->orderBudget->fresh();
    }

    public function addMember(int $orderBudgetId, int $userId, $actionUser = null, ?string $typePage = null)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);
        $user = User::findOrFail($userId);

        if ($orderBudget->users()->where('users.id', $user->id)->exists()) {
            throw new \Exception('Usuário já está adicionado a este card');
        }

        $orderBudget->users()->attach($user->id);

        if ($actionUser) {
            if ($actionUser->id === $user->id) {
                $this->historyService->logMemberJoin($orderBudgetId, $actionUser, $user, $typePage);
            } else {
                $this->historyService->logMemberJoin($orderBudgetId, $actionUser, $user, $typePage);
            }
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    public function removeMember(int $orderBudgetId, int $userId, $actionUser = null, ?string $typePage = null)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);
        $user = User::findOrFail($userId);

        if (! $orderBudget->users()->where('users.id', $user->id)->exists()) {
            throw new \Exception('Usuário não está adicionado a este card');
        }

        $orderBudget->users()->detach($user->id);

        if ($actionUser) {
            if ($actionUser->id === $user->id) {
                $this->historyService->logMemberLeave($orderBudgetId, $user, $typePage);
            } else {
                $this->historyService->logMemberRemoval($orderBudgetId, $actionUser, $user, $typePage);
            }
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    public function markAsProduced(int $orderBudgetId, $user = null, ?string $typePage = null)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);

        $orderBudget->update([
            'production_date' => now()->toDateString(),
            'production_column_names_id' => 2,
            'production_percentage' => 100,
        ]);

        if ($user) {
            $this->historyService->logProductionDateUpdate($orderBudgetId, $user, $typePage);
        }

        return $orderBudget->fresh();
    }

    public function updateProductionPercentage(int $orderBudgetId, float $percentage, $user = null, ?string $typePage = null)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);

        // Validar porcentagem (0 a 100)
        $percentage = max(0, min(100, $percentage));

        $orderBudget->update([
            'production_percentage' => $percentage,
        ]);

        if ($percentage == 100) {
            $orderBudget->update([
                'production_date' => now()->toDateString(),
            ]);
        } elseif ($percentage == 0) {
            $orderBudget->update([
                'production_date' => null,
            ]);
        }

        if ($user) {
            $this->historyService->logProductionPercentageUpdate($orderBudgetId, $user, $percentage, $typePage);
        }

        return $orderBudget->fresh();
    }

    public function updateTinyErpOrderExpeditionId(int $orderBudgetId, int $tinyErpOrderExpeditionId)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);
        $orderBudget->update([
            'tinyErp_order_expedition_id' => $tinyErpOrderExpeditionId,
        ]);

        return $orderBudget->fresh();
    }

    public function updatePickingLabelGenerated(int $orderBudgetId): OrderBudget
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);
        $orderBudget->update([
            'picking_label_generated' => 1,
        ]);

        return $orderBudget->fresh();
    }

    /**
     * @param  array<int, int>  $orderBudgetIds
     * @return int Quantidade de cards atualizados
     */
    public function updateReadyToExpedition(array $orderBudgetIds): int
    {
        $ids = array_values(array_unique(array_map('intval', $orderBudgetIds)));

        if ($ids === []) {
            return 0;
        }

        $orderIds = OrderBudget::query()
            ->whereIn('id', $ids)
            ->pluck('order_id')
            ->unique()
            ->all();

        if ($orderIds === []) {
            return 0;
        }

        return OrderBudget::query()
            ->whereIn('order_id', $orderIds)
            ->where('ready_to_expedition', 0)
            ->where('picking_label_generated', 1)
            ->update([
                'ready_to_expedition' => 1,
            ]);
    }

    public function getReadyForPicking()
    {
        return $this->orderBudget::where('production_percentage', 100)
            ->with('order')
            ->where('ready_to_expedition', 0)
            ->where('picking_label_generated', '!=', 1)
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn (OrderBudget $orderBudget) => $this->mapPickingItem($orderBudget));
    }

    public function paginateReadyForPicking(?string $search = null, bool $pickingLabelGenerated = false)
    {
        $query = OrderBudget::query()
            ->where('production_percentage', 100)
            ->with('order')
            ->where('ready_to_expedition', 0)
            ->when(
                $pickingLabelGenerated,
                fn ($query) => $query->where('picking_label_generated', 1),
                fn ($query) => $query->where('picking_label_generated', '!=', 1),
            )
            ->orderBy('id', 'asc');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('order', function ($orderQuery) use ($search) {
                    $orderQuery->where('name', 'like', "%{$search}%");
                })
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('order_id', 'like', "%{$search}%");
            });
        }

        $paginated = $query->paginate();
        $invoiceMeta = $this->resolveInvoiceMeta($paginated->getCollection()->pluck('order_id')->all());

        $paginated->getCollection()->transform(
            fn (OrderBudget $orderBudget) => $this->mapPickingItem($orderBudget, $invoiceMeta)
        );

        return $paginated;
    }

    /**
     * @param  array<int, int|string>  $orderIds
     * @return array{pending_order_ids: array<int, int>, ids_by_order: array<int, array<int, int>>, total_index_by_order: array<int, int>}
     */
    protected function resolveInvoiceMeta(array $orderIds): array
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));

        if ($orderIds === []) {
            return [
                'pending_order_ids' => [],
                'ids_by_order' => [],
                'total_index_by_order' => [],
            ];
        }

        $pendingOrderIds = OrderBudget::query()
            ->whereIn('order_id', $orderIds)
            ->where('picking_label_generated', '!=', 1)
            ->pluck('order_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->all();

        $idsByOrder = OrderBudget::query()
            ->whereIn('order_id', $orderIds)
            ->where('ready_to_expedition', 0)
            ->get(['id', 'order_id'])
            ->groupBy('order_id')
            ->map(fn ($cards) => $cards->pluck('id')->map(fn ($id) => (int) $id)->values()->all())
            ->mapWithKeys(fn ($ids, $orderId) => [(int) $orderId => $ids])
            ->all();

        $totalIndexByOrder = OrderBudget::query()
            ->whereIn('order_id', $orderIds)
            ->selectRaw('order_id, MAX(order_index) as total_index')
            ->groupBy('order_id')
            ->pluck('total_index', 'order_id')
            ->mapWithKeys(fn ($totalIndex, $orderId) => [(int) $orderId => (int) $totalIndex])
            ->all();

        return [
            'pending_order_ids' => $pendingOrderIds,
            'ids_by_order' => $idsByOrder,
            'total_index_by_order' => $totalIndexByOrder,
        ];
    }

    /**
     * @param  array{pending_order_ids?: array<int, int>, ids_by_order?: array<int, array<int, int>>, total_index_by_order?: array<int, int>}  $invoiceMeta
     * @return array<string, mixed>
     */
    protected function mapPickingItem(OrderBudget $orderBudget, array $invoiceMeta = []): array
    {
        $orderId = (int) $orderBudget->order_id;
        $pendingOrderIds = $invoiceMeta['pending_order_ids'] ?? [];
        $idsByOrder = $invoiceMeta['ids_by_order'] ?? [];
        $totalIndexByOrder = $invoiceMeta['total_index_by_order'] ?? [];

        return [
            'id' => $orderBudget->id,
            'order_id' => $orderBudget->order_id,
            'description' => $orderBudget->description,
            'name' => $orderBudget->order->name ?? $orderBudget->description ?? null,
            'production_percentage' => $orderBudget->production_percentage,
            'production_date' => $orderBudget->production_date,
            'order_index' => $orderBudget->order_index,
            'total_index' => $totalIndexByOrder[$orderId] ?? $orderBudget->order_index,
            'created_at' => $orderBudget->created_at,
            'tinyErp_order_id' => $orderBudget->tinyErp_order_id,
            'tinyErp_order_expedition_id' => $orderBudget->tinyErp_order_expedition_id,
            'ready_to_expedition' => $orderBudget->ready_to_expedition,
            'picking_label_generated' => (int) $orderBudget->picking_label_generated,
            'can_invoice_order' => ! in_array($orderId, $pendingOrderIds, true),
            'order_budget_ids' => $idsByOrder[$orderId] ?? [(int) $orderBudget->id],
            'order' => $orderBudget->order ? [
                'id' => $orderBudget->order->id,
                'name' => $orderBudget->order->name,
                'total_amount' => $orderBudget->order->total_amount,
                'status' => $orderBudget->order->status,
                'created_at' => $orderBudget->order->created_at,
            ] : null,
        ];
    }

    public function updateTinyErpOrderId(int $orderId, string $tinyErpOrderId)
    {
        $this->orderBudget::where('order_id', $orderId)->update([
            'tinyErp_order_id' => $tinyErpOrderId,
        ]);

        return $this->orderBudget->fresh();
    }

    /**
     * Sincroniza os cards da tabela order_budgets com as paredes do orçamento.
     * Mantém 1 card por parede (budget_wall_id) para o pedido informado.
     */
    public function syncFromBudget(Order $order, Budget $budget): void
    {
        $budget->loadMissing(['rooms.walls.collectionModel']);

        $defaultLayoutColumnId = LayoutColumnName::query()->orderBy('id')->value('id');
        $newLayoutsColumnId = LayoutColumnName::query()
            ->where('name', 'Novos Layouts')
            ->value('id');
        $tenantId = $order->tenant_id ?? $budget->tenant_id;

        $walls = [];
        foreach ($budget->rooms->sortBy('position') as $room) {
            foreach (collect($room->walls)->sortBy('position') as $wall) {
                $walls[] = $wall;
            }
        }

        $this->syncOrderBudgetCardsForWalls(
            $order,
            $walls,
            $tenantId,
            $defaultLayoutColumnId,
            $newLayoutsColumnId
        );
    }

    public function syncFromOrderRooms(Order $order): void
    {
        $order->loadMissing(['rooms.walls.collectionModel']);

        $defaultLayoutColumnId = LayoutColumnName::query()->orderBy('id')->value('id');
        $newLayoutsColumnId = LayoutColumnName::query()
            ->where('name', 'Novos Layouts')
            ->value('id');
        $tenantId = $order->tenant_id;

        $walls = [];
        foreach ($order->rooms->sortBy('position') as $room) {
            foreach (collect($room->walls)->sortBy('position') as $wall) {
                $walls[] = $wall;
            }
        }

        $this->syncOrderBudgetCardsForWalls(
            $order,
            $walls,
            $tenantId,
            $defaultLayoutColumnId,
            $newLayoutsColumnId
        );
    }

    /**
     * @param  array<int, BudgetWall>  $walls
     */
    protected function syncOrderBudgetCardsForWalls(
        Order $order,
        array $walls,
        ?int $tenantId,
        ?int $defaultLayoutColumnId,
        ?int $newLayoutsColumnId
    ): void {
        $existingCards = OrderBudget::query()
            ->where('order_id', $order->id)
            ->orderBy('order_index')
            ->orderBy('id')
            ->get()
            ->values();

        $usedCardIds = [];

        foreach ($walls as $index => $wall) {
            $orderIndex = $index + 1;
            $orderBudget = $existingCards->get($index);

            if ($orderBudget) {
                $orderBudget->update([
                    'budget_wall_id' => $wall->id,
                    'tenant_id' => $tenantId,
                    'description' => $wall->comment_referring_model ?? null,
                    'order_index' => $orderIndex,
                ]);

                $usedCardIds[] = $orderBudget->id;

                continue;
            }

            $orderBudget = OrderBudget::query()->create([
                'order_id' => $order->id,
                'budget_wall_id' => $wall->id,
                'status' => OrderBudgetStatus::APPROVE_LAYOUT,
                'tenant_id' => $tenantId,
                'layout_column_names_id' => $this->resolveInitialLayoutColumnId(
                    $wall->collectionModel?->name,
                    $defaultLayoutColumnId,
                    $newLayoutsColumnId
                ),
                'description' => $wall->comment_referring_model ?? null,
                'order_index' => $orderIndex,
            ]);

            $usedCardIds[] = $orderBudget->id;
        }

        OrderBudget::query()
            ->where('order_id', $order->id)
            ->when(! empty($usedCardIds), function ($query) use ($usedCardIds) {
                $query->whereNotIn('id', $usedCardIds);
            })
            ->get()
            ->each(fn (OrderBudget $card) => $this->deleteOrderBudgetCard($card));
    }

    /**
     * Desvincula cards das paredes antes de remover ambientes, preservando histórico e dependências.
     *
     * @param  iterable<int, BudgetWall>  $walls
     */
    public function detachCardsFromWalls(iterable $walls): void
    {
        $wallIds = collect($walls)->pluck('id')->filter()->values()->all();

        if ($wallIds === []) {
            return;
        }

        OrderBudget::query()
            ->whereIn('budget_wall_id', $wallIds)
            ->update(['budget_wall_id' => null]);
    }

    protected function deleteOrderBudgetCard(OrderBudget $card): void
    {
        DB::table('request_layouts_art')
            ->where('order_budget_id', $card->id)
            ->delete();

        DB::table('request_layouts_art_interactions')
            ->where('card_id', $card->id)
            ->delete();

        $card->delete();
    }

    protected function resolveInitialLayoutColumnId(
        ?string $collectionModelName,
        ?int $defaultLayoutColumnId,
        ?int $newLayoutsColumnId
    ): ?int {
        $normalizedName = mb_strtolower(trim((string) $collectionModelName));
        $shouldStartOnNewLayouts = in_array($normalizedName, [
            'arte do shutterstock',
            'coleção arts',
            'colecao arts',
        ], true);

        if ($shouldStartOnNewLayouts && $newLayoutsColumnId) {
            return $newLayoutsColumnId;
        }

        return $defaultLayoutColumnId;
    }
}

<?php

namespace App\Repositories;

use App\Actions\Budget\DuplicateBudgetAction;
use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\BudgetRoom;
use App\Models\Order;
use App\Models\OrderBudget;
use App\Services\LayoutCardHistoryService;
use App\Support\Budget\BudgetCalculator;
use App\Support\OrderBudgetStatus;
use App\Support\OrderStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BudgetRepository
{
    protected $historyService;

    public function __construct(
        LayoutCardHistoryService $historyService,
        protected OrderRepository $orderRepository,
        protected OrderBudgetRepository $orderBudgetRepository,
        protected DropshippingRepository $dropshippingRepository,
    ) {
        $this->historyService = $historyService;
    }

    public function all()
    {
        return Budget::with('rooms.walls.collectionModel')->get();
    }

    public function getPendingReview()
    {
        return Budget::with('rooms.walls.collectionModel')
            ->whereIn('status', OrderBudgetStatus::variants(OrderBudgetStatus::PENDING_REVIEW))
            ->get();
    }

    public function getAll()
    {
        $user = Auth::user();

        $query = Budget::with('rooms.walls.collectionModel');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query->get();
    }

    public function getAllById(int $id)
    {
        return Budget::with(['rooms.walls.collectionModel', 'primaryRoom.walls.collectionModel'])
            ->where('id', $id)
            ->first();
    }

    public function create(array $data)
    {
        $rooms = $data['rooms'];

        $selectedCarrier = $data['selectedCarrier'] ?? null;

        $totalArea = BudgetCalculator::calculateTotalArea($rooms);
        $totalAmount = BudgetCalculator::calculateTotalAmountVista(
            $totalArea,
            $rooms,
            $selectedCarrier
        );
        $totalAmountInstallments = BudgetCalculator::calculateTotalAmountPrazo(
            $totalArea,
            $rooms,
            $selectedCarrier
        );
        $deliveryTime = BudgetCalculator::calculateDeliveryTime($rooms, $selectedCarrier);

        /** @var Budget $budget */
        $budget = DB::transaction(function () use (
            $data,
            $rooms,
            $selectedCarrier,
            $totalArea,
            $totalAmount,
            $totalAmountInstallments,
            $deliveryTime
        ) {
            $user = Auth::user();
            $tenantId = $user->isTenant() ? $user->id : null;

            $budget = Budget::query()->create([
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'total_area' => $totalArea,
                'total_amount' => $totalAmount,
                'total_amount_installments' => $totalAmountInstallments,
                'delivery_time' => $deliveryTime,
                'cep' => $data['cep'] ?? null,
                'selected_carrier_name' => $selectedCarrier['name'] ?? null,
                'selected_carrier_price' => $selectedCarrier['price'] ?? null,
                'selected_carrier_delivery_time' => $selectedCarrier['deliveryTime'] ?? null,
                'carriers_snapshot' => null,
                'dropshipping_budget' => isset($data['dropshipping_budget']) ? (int) $data['dropshipping_budget'] : 0,
                'raw_payload' => $data,
                'status' => BudgetStatus::Open->value,
            ]);

            foreach ($rooms as $roomIndex => $roomData) {
                $room = $budget->rooms()->create([
                    'tenant_id' => $tenantId,
                    'name' => $roomData['name'] ?? null,
                    'position' => $roomIndex,
                    'raw_payload' => $roomData,
                ]);

                $wallsSequence = BudgetCalculator::calculateWallsSequence($roomData['walls'] ?? []);

                foreach ($roomData['walls'] as $wallIndex => $wallData) {
                    $collectionModelId = $wallData['model'] ?? null;
                    $wallMetrics = $wallsSequence['perWall'][$wallIndex] ?? null;

                    $totalAreaWall = (float) ($wallMetrics['total_area'] ?? 0);
                    $stripCount = (int) ($wallMetrics['strip_count'] ?? 0);
                    $stripHeight = $wallMetrics['strip_height'] ?? null;

                    $room->walls()->create([
                        'tenant_id' => $tenantId,
                        'name' => $wallData['name'] ?? null,
                        'direction' => $wallData['direction'] ?? null,
                        'position' => $wallIndex,
                        'width' => $wallData['width'] ?? null,
                        'height' => $wallData['height'] ?? null,
                        'continue_same_art' => (bool) ($wallData['continueSameArt'] ?? false),
                        'continuations' => $wallData['continuations'] ?? [],
                        'collection_model_id' => $collectionModelId,
                        'total_area' => $totalAreaWall,
                        'strip_height' => $stripHeight,
                        'strip_count' => $stripCount,
                        'comment_referring_model' => $wallData['comment_referring_model'] ?? null,
                        'link_referring_model' => $wallData['link_referring_model'] ?? null,
                        'files_referring_model' => isset($wallData['files_referring_model'])
                            ? (array) $wallData['files_referring_model']
                            : null,
                        'collection_referring_model' => $this->formatCollectionReferringModel(
                            $wallData['collection_referring_model'] ?? null
                        ),
                    ]);
                }
            }

            $primaryRoomId = $budget->rooms()->orderBy('position')->value('id');

            if ($primaryRoomId) {
                $budget->update(['primary_budget_room_id' => $primaryRoomId]);
            }

            return $budget->load(['rooms.walls.collectionModel']);
        });

        return $budget;
    }

    public function update(Budget $budget, array $data)
    {
        $rooms = $data['rooms'];

        $selectedCarrier = $data['selectedCarrier'] ?? null;

        $totalArea = BudgetCalculator::calculateTotalArea($rooms);
        $totalAmount = BudgetCalculator::calculateTotalAmountVista(
            $totalArea,
            $rooms,
            $selectedCarrier
        );
        $totalAmountInstallments = BudgetCalculator::calculateTotalAmountPrazo(
            $totalArea,
            $rooms,
            $selectedCarrier
        );
        $deliveryTime = BudgetCalculator::calculateDeliveryTime($rooms, $selectedCarrier);

        /** @var Budget $budget */
        $budget = DB::transaction(function () use (
            $budget,
            $data,
            $rooms,
            $selectedCarrier,
            $totalArea,
            $totalAmount,
            $totalAmountInstallments,
            $deliveryTime
        ) {
            // Atualizar dados do orçamento
            $budget->update([
                'name' => $data['name'],
                'total_area' => $totalArea,
                'total_amount' => $totalAmount,
                'total_amount_installments' => $totalAmountInstallments,
                'delivery_time' => $deliveryTime,
                'cep' => $data['cep'] ?? null,
                'selected_carrier_name' => $selectedCarrier['name'] ?? null,
                'selected_carrier_price' => $selectedCarrier['price'] ?? null,
                'selected_carrier_delivery_time' => $selectedCarrier['deliveryTime'] ?? null,
                'carriers_snapshot' => null,
                'dropshipping_budget' => isset($data['dropshipping_budget']) ? (int) $data['dropshipping_budget'] : $budget->dropshipping_budget,
                'raw_payload' => $data,
                'status' => $data['status'] ?? $budget->status,
            ]);

            // Preservar tenant_id do orçamento existente
            $tenantId = $budget->tenant_id;

            // Remover rooms e walls antigas
            $budget->rooms()->delete();

            // Criar novas rooms e walls
            foreach ($rooms as $roomIndex => $roomData) {
                $room = $budget->rooms()->create([
                    'tenant_id' => $tenantId,
                    'name' => $roomData['name'] ?? null,
                    'position' => $roomIndex,
                    'raw_payload' => $roomData,
                ]);

                $wallsSequence = BudgetCalculator::calculateWallsSequence($roomData['walls'] ?? []);

                foreach ($roomData['walls'] as $wallIndex => $wallData) {
                    $collectionModelId = $wallData['model'] ?? null;
                    $wallMetrics = $wallsSequence['perWall'][$wallIndex] ?? null;

                    $totalAreaWall = (float) ($wallMetrics['total_area'] ?? 0);
                    $stripCount = (int) ($wallMetrics['strip_count'] ?? 0);
                    $stripHeight = $wallMetrics['strip_height'] ?? null;

                    $room->walls()->create([
                        'tenant_id' => $tenantId,
                        'name' => $wallData['name'] ?? null,
                        'direction' => $wallData['direction'] ?? null,
                        'position' => $wallIndex,
                        'width' => $wallData['width'] ?? null,
                        'height' => $wallData['height'] ?? null,
                        'continue_same_art' => (bool) ($wallData['continueSameArt'] ?? false),
                        'continuations' => $wallData['continuations'] ?? [],
                        'collection_model_id' => $collectionModelId,
                        'total_area' => $totalAreaWall,
                        'strip_height' => $stripHeight,
                        'strip_count' => $stripCount,
                        'comment_referring_model' => $wallData['comment_referring_model'] ?? null,
                        'link_referring_model' => $wallData['link_referring_model'] ?? null,
                        'files_referring_model' => isset($wallData['files_referring_model'])
                            ? (array) $wallData['files_referring_model']
                            : null,
                        'collection_referring_model' => $this->formatCollectionReferringModel(
                            $wallData['collection_referring_model'] ?? null
                        ),
                    ]);
                }
            }

            $primaryRoomId = $budget->rooms()->orderBy('position')->value('id');

            if ($primaryRoomId) {
                $budget->update(['primary_budget_room_id' => $primaryRoomId]);
            }

            return $budget->load(['rooms.walls.collectionModel']);
        });

        return $budget;
    }

    public function updateMarkup(Budget $budget, float $markup): Budget
    {
        $budget->update([
            'total_amount_markup' => $budget['total_amount'] * $markup,
            'total_amount_installments_markup' => $budget['total_amount_installments'] * $markup,
        ]);

        return $budget->fresh(['rooms.walls.collectionModel']);
    }

    public function cancel(Budget $budget): Budget
    {
        $budget->update([
            'status' => BudgetStatus::Canceled->value,
        ]);

        return $budget->fresh(['rooms.walls.collectionModel']);
    }

    public function placeOrder(Budget $budget, array $data = []): Budget
    {
        return DB::transaction(function () use ($budget, $data) {
            $budget->loadMissing(['rooms.walls.collectionModel']);

            $budget->update([
                'status' => BudgetStatus::Approved->value,
            ]);

            $wallsById = collect($data['walls'] ?? [])
                ->filter(fn ($wall) => is_array($wall) && ! empty($wall['id']))
                ->keyBy(fn ($wall) => (int) $wall['id']);

            // Processar dados mapeados por parede (formato legado)
            $wallDataMapping = [];
            if (! empty($data['wall_referring_model_data'])) {
                $decoded = json_decode($data['wall_referring_model_data'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $wallDataMapping = $decoded;
                }
            }

            // Processar collection_referring_model mapeado por parede
            $wallImageMapping = [];
            if (! empty($data['collection_referring_model'])) {
                $decoded = json_decode($data['collection_referring_model'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $wallImageMapping = $decoded;
                }
            }

            $allNewFiles = [];

            foreach ($budget->rooms as $room) {
                foreach ($room->walls as $wall) {
                    $wallUpdatePayload = [];
                    $wallPayload = $wallsById->get($wall->id);

                    if (is_array($wallPayload)) {
                        if (array_key_exists('model', $wallPayload)) {
                            $wallUpdatePayload['collection_model_id'] = $wallPayload['model'] !== null && $wallPayload['model'] !== ''
                                ? (int) $wallPayload['model']
                                : null;
                        }

                        if (array_key_exists('comment_referring_model', $wallPayload)) {
                            $comment = $wallPayload['comment_referring_model'];
                            $wallUpdatePayload['comment_referring_model'] = $comment !== null && $comment !== '' ? $comment : null;
                        }

                        if (array_key_exists('link_referring_model', $wallPayload)) {
                            $link = $wallPayload['link_referring_model'];
                            $wallUpdatePayload['link_referring_model'] = $link !== null && $link !== '' ? $link : null;
                        }

                        if (array_key_exists('files_referring_model', $wallPayload)) {
                            $files = is_array($wallPayload['files_referring_model'])
                                ? array_values(array_filter(array_map(
                                    static fn ($file) => trim((string) $file),
                                    $wallPayload['files_referring_model']
                                ), static fn ($file) => $file !== ''))
                                : [];
                            $wallUpdatePayload['files_referring_model'] = $files ?: null;
                        }

                        if (array_key_exists('collection_referring_model', $wallPayload)) {
                            $wallUpdatePayload['collection_referring_model'] = $this->formatCollectionReferringModel(
                                $wallPayload['collection_referring_model'] ?? null
                            );
                        }
                    }

                    if (isset($wallDataMapping[$wall->id])) {
                        $wallData = $wallDataMapping[$wall->id];

                        if (isset($wallData['comment'])) {
                            $comment = $wallData['comment'];
                            $wallUpdatePayload['comment_referring_model'] = $comment !== null && $comment !== '' ? $comment : null;
                        }

                        if (isset($wallData['link'])) {
                            $link = $wallData['link'];
                            $wallUpdatePayload['link_referring_model'] = $link !== null && $link !== '' ? $link : null;
                        }
                    }

                    $wallFiles = null;
                    if (isset($data['wall_files']) && is_array($data['wall_files'])) {
                        if (isset($data['wall_files'][$wall->id]) && is_array($data['wall_files'][$wall->id])) {
                            $wallFiles = $data['wall_files'][$wall->id];
                        }
                    }

                    $wallFilesKey = "wall_files.{$wall->id}";
                    if (! $wallFiles && isset($data[$wallFilesKey])) {
                        $wallFiles = $data[$wallFilesKey];
                        if (! is_array($wallFiles)) {
                            $wallFiles = [$wallFiles];
                        }
                    }

                    if ($wallFiles && is_array($wallFiles) && ! empty($wallFiles)) {
                        $existingFiles = is_array($wall->files_referring_model)
                            ? $wall->files_referring_model
                            : [];

                        $uploadedFiles = [];
                        foreach ($wallFiles as $file) {
                            if ($file instanceof UploadedFile) {
                                $uploadedFiles[] = Storage::disk('public')->putFile('budgets/referring-models', $file);
                            }
                        }

                        $mergedFiles = array_values(array_filter(array_unique(array_merge($existingFiles, $uploadedFiles))));
                        $newFiles = array_diff($mergedFiles, $existingFiles);
                        $allNewFiles = array_merge($allNewFiles, $newFiles);

                        if (! empty($mergedFiles)) {
                            $wallUpdatePayload['files_referring_model'] = $mergedFiles;
                        }
                    }

                    if (isset($wallImageMapping[$wall->id])) {
                        $wallUpdatePayload['collection_referring_model'] = $wallImageMapping[$wall->id];
                    }

                    if (! empty($wallUpdatePayload)) {
                        $wall->update($wallUpdatePayload);
                    }
                }
            }

            $budget->refresh()->load(['rooms.walls']);
            $this->recalculateBudgetTotalsFromRooms($budget);

            if ($budget->order_id) {
                $order = Order::query()->findOrFail($budget->order_id);
                $this->orderRepository->syncFromBudget($order, $budget);
            } else {
                $order = $this->orderRepository->createFromBudget($budget);
                // Persiste o vínculo: budgets.order_id = id do pedido recém-criado
                $budget->update(['order_id' => $order->getKey()]);
            }

            BudgetRoom::query()
                ->where('budget_id', $budget->id)
                ->update(['order_id' => $order->id]);

            // Garante um card (order_budget) por parede do orçamento no pedido
            $this->orderBudgetRepository->syncFromBudget($order, $budget);

            if (! empty($allNewFiles) && Auth::check()) {
                $user = Auth::user();
                $orderBudgets = OrderBudget::where('order_id', $order->id)->get();

                foreach ($allNewFiles as $filePath) {
                    $fileName = basename($filePath);
                    $fileUrl = asset('storage/'.$filePath);

                    foreach ($orderBudgets as $orderBudget) {
                        $this->historyService->logFileAttachment($orderBudget->id, $user, $fileName, $fileUrl);
                    }
                }
            }

            // Insere ID do pedido no dropshipping do orçamento associado
            $dropshippingData = $this->dropshippingRepository->findDropshippingByBudgetId($budget->id);
            if ($dropshippingData) {
                $dropshippingData->update([
                    'order_id' => $order->id,
                ]);
            }

            return $budget->fresh(['rooms.walls.collectionModel', 'order']);
        });
    }

    /**
     * Recalcula totais e prazo do orçamento a partir das paredes já persistidas.
     */
    protected function recalculateBudgetTotalsFromRooms(Budget $budget): void
    {
        $rooms = [];

        foreach ($budget->rooms->sortBy('position') as $room) {
            $walls = [];

            foreach ($room->walls->sortBy('position') as $wall) {
                $walls[] = [
                    'model' => $wall->collection_model_id,
                    'width' => $wall->width,
                    'height' => $wall->height,
                    'continueSameArt' => (bool) $wall->continue_same_art,
                    'continuations' => $wall->continuations ?? [],
                ];
            }

            $rooms[] = [
                'name' => $room->name,
                'walls' => $walls,
            ];
        }

        $selectedCarrier = $budget->selected_carrier_price !== null
            ? [
                'name' => $budget->selected_carrier_name,
                'price' => (float) $budget->selected_carrier_price,
                'deliveryTime' => (int) ($budget->selected_carrier_delivery_time ?? 0),
            ]
            : null;

        $totalArea = BudgetCalculator::calculateTotalArea($rooms);
        $totalAmount = BudgetCalculator::calculateTotalAmountVista($totalArea, $rooms, $selectedCarrier);
        $totalAmountInstallments = BudgetCalculator::calculateTotalAmountPrazo($totalArea, $rooms, $selectedCarrier);
        $deliveryTime = BudgetCalculator::calculateDeliveryTime($rooms, $selectedCarrier);

        $budget->update([
            'total_area' => $totalArea,
            'total_amount' => $totalAmount,
            'total_amount_installments' => $totalAmountInstallments,
            'delivery_time' => $deliveryTime,
        ]);
    }

    protected function formatCollectionReferringModel($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $normalized = array_map(
                static fn ($item) => trim((string) $item),
                $value
            );

            $filtered = array_values(
                array_filter($normalized, static fn ($item) => $item !== '')
            );

            return $filtered ? implode(',', array_unique($filtered)) : null;
        }

        $stringValue = trim((string) $value);

        return $stringValue !== '' ? $stringValue : null;
    }

    /**
     * @deprecated
     */
    public function duplicate(Budget $source): Budget
    {
        return app(DuplicateBudgetAction::class)->execute($source);
    }

    public function registerPayment(Order $order, UploadedFile $file): Order
    {
        return DB::transaction(function () use ($order, $file) {
            $path = $file->store('payments', ['disk' => 'public']);

            if (! $path) {
                throw new \Exception('Erro ao fazer upload do arquivo de pagamento.');
            }

            // Atualizar o pedido com o arquivo de pagamento e status
            $order->payment_file = $path;
            $order->status = OrderStatus::APPROVED;
            $order->paid = 1;
            $order->payment_status = 'paid';
            $order->save();

            // Atualizar todos os order_budgets associados para 'Aprovado'
            OrderBudget::where('order_id', $order->id)
                ->update(['status' => OrderBudgetStatus::APPROVED]);

            return $order->fresh();
        });
    }
}

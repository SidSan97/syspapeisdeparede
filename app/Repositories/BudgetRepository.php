<?php

namespace App\Repositories;

use App\Models\Budget;
use App\Support\Budget\BudgetCalculator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\OrderBudget;
use App\Services\LayoutCardHistoryService;

class BudgetRepository {

    protected $historyService;

    public function __construct(LayoutCardHistoryService $historyService)
    {
        $this->historyService = $historyService;
    }

    public function all()
    {
        return Budget::with('rooms.walls.collectionModel')->get();
    }

    public function getPendingReview()
    {
        return Budget::with('rooms.walls.collectionModel')
            ->where('status', 'Pendente de Revisão')
            ->get();
    }

    public function getAll()
    {
        $user = Auth::user();

        $query = Budget::with('rooms.walls.collectionModel');

        if(!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query->get();
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

        /** @var \App\Models\Budget $budget */
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

            $budget = Budget::create([
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'total_area' => $totalArea,
                'total_amount' => $totalAmount,
                'total_amount_installments' => $totalAmountInstallments,
                'delivery_time' => $deliveryTime,
                'payment_method' => $data['paymentMethod'] ?? null,
                'installment_limit' => ($data['paymentMethod'] ?? null) === 'credit_card'
                    ? ($data['installmentLimit'] ?? null)
                    : null,
                'installments' => ($data['paymentMethod'] ?? null) === 'credit_card'
                    ? (int) ($data['installments'] ?? 1)
                    : null,
                'cep' => $data['cep'] ?? null,
                'selected_carrier_name' => $selectedCarrier['name'] ?? null,
                'selected_carrier_price' => $selectedCarrier['price'] ?? null,
                'selected_carrier_delivery_time' => $selectedCarrier['deliveryTime'] ?? null,
                'carriers_snapshot' => null,
                'comment_referring_model' => $data['commentReferringModel'] ?? null,
                'link_referring_model' => $data['linkReferringModel'] ?? null,
                'files_referring_model' => isset($data['filesReferringModel'])
                    ? (array) $data['filesReferringModel']
                    : null,
                'collection_referring_model' => $this->formatCollectionReferringModel(
                    $data['collectionReferringModel'] ?? null
                ),
                'dropshipping_budget' => isset($data['dropshipping_budget']) ? (int) $data['dropshipping_budget'] : 0,
                'raw_payload' => $data,
                'status' => 'Em aberto',
            ]);

            foreach ($rooms as $roomIndex => $roomData) {
                $room = $budget->rooms()->create([
                    'tenant_id' => $tenantId,
                    'name' => $roomData['name'] ?? null,
                    'position' => $roomIndex,
                    'raw_payload' => $roomData,
                ]);

                foreach ($roomData['walls'] as $wallIndex => $wallData) {
                    $collectionModelId = $wallData['model'] ?? null;
                    $totalAreaWall = BudgetCalculator::calculateWallArea($wallData);
                    $stripCount = BudgetCalculator::calculateStripCount($wallData);
                    $stripHeight = BudgetCalculator::calculateStripHeight($wallData);

                    $room->walls()->create([
                        'tenant_id' => $tenantId,
                        'name' => $wallData['name'] ?? null,
                        'position' => $wallIndex,
                        'width' => $wallData['width'] ?? null,
                        'height' => $wallData['height'] ?? null,
                        'continue_same_art' => (bool) ($wallData['continueSameArt'] ?? false),
                        'continuations' => $wallData['continuations'] ?? [],
                        'collection_model_id' => $collectionModelId,
                        'total_area' => $totalAreaWall,
                        'strip_height' => $stripHeight,
                        'strip_count' => $stripCount,
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

        /** @var \App\Models\Budget $budget */
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
                'payment_method' => $data['paymentMethod'] ?? null,
                'installment_limit' => ($data['paymentMethod'] ?? null) === 'credit_card'
                    ? ($data['installmentLimit'] ?? null)
                    : null,
                'installments' => ($data['paymentMethod'] ?? null) === 'credit_card'
                    ? (int) ($data['installments'] ?? 1)
                    : null,
                'cep' => $data['cep'] ?? null,
                'selected_carrier_name' => $selectedCarrier['name'] ?? null,
                'selected_carrier_price' => $selectedCarrier['price'] ?? null,
                'selected_carrier_delivery_time' => $selectedCarrier['deliveryTime'] ?? null,
                'carriers_snapshot' => null,
                'comment_referring_model' => $data['commentReferringModel'] ?? null,
                'link_referring_model' => $data['linkReferringModel'] ?? null,
                'files_referring_model' => isset($data['filesReferringModel'])
                    ? (array) $data['filesReferringModel']
                    : null,
                'collection_referring_model' => $this->formatCollectionReferringModel(
                    $data['collectionReferringModel'] ?? null
                ),
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

                foreach ($roomData['walls'] as $wallIndex => $wallData) {
                    $collectionModelId = $wallData['model'] ?? null;
                    $totalAreaWall = BudgetCalculator::calculateWallArea($wallData);
                    $stripCount = BudgetCalculator::calculateStripCount($wallData);
                    $stripHeight = BudgetCalculator::calculateStripHeight($wallData);

                    $room->walls()->create([
                        'tenant_id' => $tenantId,
                        'name' => $wallData['name'] ?? null,
                        'position' => $wallIndex,
                        'width' => $wallData['width'] ?? null,
                        'height' => $wallData['height'] ?? null,
                        'continue_same_art' => (bool) ($wallData['continueSameArt'] ?? false),
                        'continuations' => $wallData['continuations'] ?? [],
                        'collection_model_id' => $collectionModelId,
                        'total_area' => $totalAreaWall,
                        'strip_height' => $stripHeight,
                        'strip_count' => $stripCount,
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

    public function cancel(Budget $budget): Budget
    {
        $budget->update([
            'status' => 'cancelado',
        ]);

        return $budget->fresh(['rooms.walls.collectionModel']);
    }

    public function placeOrder(Budget $budget, array $data): Budget
    {
        $budget->loadMissing(['rooms.walls.collectionModel']);

        $existingFiles = is_array($budget->files_referring_model)
            ? $budget->files_referring_model
            : [];

        $uploadedFiles = [];

        if (!empty($data['files_referring_model'])) {
            foreach ($data['files_referring_model'] as $file) {
                if ($file instanceof UploadedFile) {
                    $uploadedFiles[] = Storage::disk('public')->putFile('budgets/referring-models', $file);
                }
            }
        }

        $mergedFiles = array_values(array_filter(array_unique(array_merge($existingFiles, $uploadedFiles))));

        $updatePayload = [
            'status' => 'Pendente de Revisão',
        ];

        if (array_key_exists('comment_referring_model', $data)) {
            $comment = $data['comment_referring_model'];
            $updatePayload['comment_referring_model'] = $comment !== null && $comment !== '' ? $comment : null;
        }

        if (array_key_exists('link_referring_model', $data)) {
            $link = $data['link_referring_model'];
            $updatePayload['link_referring_model'] = $link !== null && $link !== '' ? $link : null;
        }

        $newFiles = [];
        if (!empty($mergedFiles)) {
            $updatePayload['files_referring_model'] = $mergedFiles;
            // Identificar novos arquivos adicionados
            $newFiles = array_diff($mergedFiles, $existingFiles);
        } elseif (array_key_exists('files_referring_model', $data)) {
            $updatePayload['files_referring_model'] = null;
        }

        if (array_key_exists('collection_referring_model', $data)) {
            $collection = $data['collection_referring_model'];
            $updatePayload['collection_referring_model'] = $collection !== null && $collection !== '' ? $collection : null;
        }

        $budget->update($updatePayload);

        // Registrar no histórico os novos arquivos adicionados
        if (!empty($newFiles) && Auth::check()) {
            $user = Auth::user();
            // Buscar todos os OrderBudgets relacionados a este budget
            $orderBudgets = OrderBudget::where('budget_id', $budget->id)->get();

            foreach ($newFiles as $filePath) {
                $fileName = basename($filePath);
                // Criar URL pública para o arquivo
                $fileUrl = asset('storage/' . $filePath);

                // Registrar em cada card relacionado
                foreach ($orderBudgets as $orderBudget) {
                    $this->historyService->logFileAttachment($orderBudget->id, $user, $fileName, $fileUrl);
                }
            }
        }

        return $budget->fresh(['rooms.walls.collectionModel']);
    }

    public function getLayoutsForApprove()
    {
        return OrderBudget::whereIn('status', ['Aprovar Layout', 'Pendente de Revisão'])
            ->whereNotNull('budget_wall_id')
            ->with([
                'budget' => function ($query) {
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
        return OrderBudget::whereIn('status', ['Aprovado', 'Liberado para produção'])
            ->whereNotNull('budget_wall_id')
            ->with([
                'budget' => function ($query) {
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

    public function registerPayment(Budget $budget, UploadedFile $file): Budget
    {
        return DB::transaction(function () use ($budget, $file) {
            $path = $file->store('payments', ['disk' => 'public']);

            if (!$path) {
                throw new \Exception('Erro ao fazer upload do arquivo de pagamento.');
            }

            // Atualizar o budget com o arquivo de pagamento e status
            $budget->payment_file = $path;
            $budget->status = 'Aprovado';
            $budget->save();

            // Atualizar todos os order_budgets associados para 'Aprovado'
            OrderBudget::where('budget_id', $budget->id)
                ->update(['status' => 'Aprovado']);

            return $budget->fresh();
        });
    }
}

<?php

namespace App\Services;

use App\Models\CollectionImage;
use App\Models\OrderBudget;
use App\Repositories\OrderBudgetRepository;
use App\Repositories\OrderRepository;
use App\Support\Budget\BudgetCalculator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ExpeditionService
{
    protected $orderRepository;

    protected $orderBudgetRepository;

    protected $tinyErpService;

    public function __construct(
        OrderRepository $orderRepository,
        OrderBudgetRepository $orderBudgetRepository,
        TinyErpService $tinyErpService
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderBudgetRepository = $orderBudgetRepository;
        $this->tinyErpService = $tinyErpService;
    }

    /**
     * @return array{
     *     title: string,
     *     status: string,
     *     carrier_name: ?string,
     *     card_name: ?string,
     *     model_name: ?string,
     *     model_art_name: ?string,
     *     observation: ?string,
     *     layout_quantity: string,
     *     strip_groups: array<int, array{q: int, h: float}>
     * }
     */
    public function generateLabelSeparation(object $orderBudgets, object $order): array
    {
        $orderBudget = $orderBudgets instanceof OrderBudget
            ? $orderBudgets->loadMissing(['wall.room', 'wall.collectionModel'])
            : $orderBudgets;

        $order->loadMissing(['tenant']);

        $wall = $orderBudget->wall;
        $orderIndex = (int) $orderBudget->order_index;
        $maxIndex = (int) ($order->orderBudgets()->max('order_index') ?? $orderIndex);
        $formattedOrderId = str_pad((string) $orderBudget->order_id, 5, '0', STR_PAD_LEFT);
        $resellerName = $order->tenant?->name ?: 'Revenda';

        $roomName = $wall?->room?->name ?? 'Ambiente';
        $wallName = $wall?->name ?? 'Parede';
        $cardName = trim(($order->name ?? '').' - '.$roomName.' - '.$wallName, ' -');

        $carrierName = null;
        if (! empty($order->selected_carrier_name)) {
            $carrierName = trim(explode(' - ', (string) $order->selected_carrier_name)[0]);
        }

        if ($orderIndex === 1 && $orderIndex < $maxIndex) {
            $status = 'incompleto';
        } elseif ($orderIndex > 1 && $orderIndex < $maxIndex) {
            $status = 'complemento incompleto';
        } else {
            $status = 'complemento completo';
        }

        $modelName = $wall?->collectionModel?->name;
        $modelArtName = null;
        if ($this->isColecaoArtsModel($modelName)) {
            $modelArtName = $this->resolveCollectionArtName($wall?->collection_referring_model ?? null);
        }

        $label = [
            'title' => $formattedOrderId.' - '.$resellerName,
            'status' => $status,
            'carrier_name' => $carrierName,
            'card_name' => $cardName !== '' ? $cardName : null,
            'model_name' => $modelName,
            'model_art_name' => $modelArtName,
            'observation' => $order->observation,
            'layout_quantity' => $orderIndex.'/'.$maxIndex,
            'strip_groups' => $this->buildStripGroups($wall),
        ];

        $this->orderBudgetRepository->updateReadyToExpedition($orderBudget->id);

        return $label;
    }

    /**
     * Gera etiquetas de separação para vários order budgets.
     *
     * @param  array<int, int>  $orderBudgetIds
     * @return array<int, array<string, mixed>>
     */
    public function generateLabelsSeparation(array $orderBudgetIds): array
    {
        $labels = [];

        foreach ($orderBudgetIds as $orderBudgetId) {
            $orderBudget = $this->orderBudgetRepository->show((int) $orderBudgetId);
            $order = $this->orderRepository->find($orderBudget->order_id);
            $labels[] = $this->generateLabelSeparation($orderBudget, $order);
        }

        return $labels;
    }

    protected function isColecaoArtsModel(?string $modelName): bool
    {
        $normalizedName = mb_strtolower(trim((string) $modelName));

        return in_array($normalizedName, [
            'coleção arts',
            'colecao arts',
            'coleção art',
            'colecao art',
        ], true);
    }

    protected function resolveCollectionArtName(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $imageId = null;
        if (is_int($value) && $value > 0) {
            $imageId = $value;
        } else {
            $stringValue = trim((string) $value);
            if ($stringValue !== '' && ctype_digit($stringValue)) {
                $imageId = (int) $stringValue;
            } elseif ($stringValue !== '') {
                return $stringValue;
            }
        }

        if ($imageId === null || $imageId <= 0) {
            return null;
        }

        $image = CollectionImage::query()->find($imageId);
        if (! $image) {
            return null;
        }

        $name = trim((string) ($image->name ?? ''));

        return $name !== '' ? $name : null;
    }

    /**
     * @return array<int, array{q: int, h: float}>
     */
    protected function buildStripGroups(?object $wall): array
    {
        if ($wall === null) {
            return [];
        }

        $sequence = BudgetCalculator::calculateWallWithContinuations(
            BudgetCalculator::normalizeWallForCalculation($wall)
        );

        $groups = [];
        foreach ($sequence['groups'] ?? [] as $group) {
            $quantity = (int) ($group['q'] ?? 0);
            $height = (float) ($group['h'] ?? 0);

            if ($quantity <= 0 || $height <= 0) {
                continue;
            }

            $groups[] = [
                'q' => $quantity,
                'h' => $height,
            ];
        }

        if ($groups !== []) {
            return $groups;
        }

        $stripCount = (int) ($wall->strip_count ?? 0);
        $stripHeight = (float) ($wall->strip_height ?? 0);

        if ($stripCount > 0 && $stripHeight > 0) {
            return [[
                'q' => $stripCount,
                'h' => $stripHeight,
            ]];
        }

        return [];
    }

    /**
     * Valida se todas as notas fiscais têm o mesmo transportador
     *
     * @param  array  $invoiceIds  Array de IDs das notas fiscais
     * @param  string|null  $expectedCarrier  Nome do transportador esperado (opcional)
     *
     * @throws ValidationException
     */
    public function validateSameCarrier(array $invoiceIds, ?string $expectedCarrier = null): void
    {
        if (empty($invoiceIds)) {
            throw ValidationException::withMessages([
                'invoice_ids' => 'Nenhuma nota fiscal foi fornecida para validação.',
            ]);
        }

        $cacheKey = 'tiny_erp_invoices';
        $cachedData = Cache::get($cacheKey);

        if ($cachedData === null) {
            $cachedData = $this->tinyErpService->searchInvoices();
            Cache::put($cacheKey, $cachedData, now()->addHours(24));
        }

        $allInvoices = [];
        if (isset($cachedData['notas_fiscais']) && is_array($cachedData['notas_fiscais'])) {
            $allInvoices = $cachedData['notas_fiscais'];
        } elseif (is_array($cachedData)) {
            $allInvoices = $cachedData;
        }

        $invoiceIdsInt = array_map('intval', $invoiceIds);

        $selectedInvoices = [];
        foreach ($allInvoices as $invoice) {
            $invoiceId = null;
            if (isset($invoice['nota_fiscal']['id'])) {
                $invoiceId = (int) $invoice['nota_fiscal']['id'];
            } elseif (isset($invoice['id'])) {
                $invoiceId = (int) $invoice['id'];
            }

            if ($invoiceId && in_array($invoiceId, $invoiceIdsInt, true)) {
                $selectedInvoices[] = $invoice;
            }
        }

        if (count($selectedInvoices) !== count($invoiceIds)) {
            throw ValidationException::withMessages([
                'invoice_ids' => 'Uma ou mais notas fiscais não foram encontradas.',
            ]);
        }

        $transporters = [];
        foreach ($selectedInvoices as $invoice) {
            $notaFiscal = $invoice['nota_fiscal'] ?? $invoice;
            $transporter = $notaFiscal['transportador']['nome'] ?? null;

            if (empty($transporter)) {
                throw ValidationException::withMessages([
                    'invoice_ids' => 'Uma ou mais notas fiscais não possuem transportador definido.',
                ]);
            }

            $transporters[] = trim($transporter);
        }

        $uniqueTransporters = array_unique($transporters);

        if (count($uniqueTransporters) > 1) {
            throw ValidationException::withMessages([
                'invoice_ids' => 'Todas as notas fiscais selecionadas devem ter exatamente o mesmo transportador.',
            ]);
        }

        if ($expectedCarrier !== null) {
            $firstTransporter = reset($uniqueTransporters);
            if (trim($expectedCarrier) !== $firstTransporter) {
                throw ValidationException::withMessages([
                    'carrier' => 'O transportador informado não corresponde ao transportador das notas fiscais selecionadas.',
                ]);
            }
        }
    }

    public function filterInvoices(array $nfs, array $dropshippings): array
    {
        $filteredInvoices = [];

        $normalizeCpfCnpj = function ($cpfCnpj) {
            return preg_replace('/[^0-9]/', '', $cpfCnpj ?? '');
        };

        $dropshippingsByOrderId = [];
        foreach ($dropshippings as $dropshipping) {
            $orderId = (string) $dropshipping['order_id'];
            if (! isset($dropshippingsByOrderId[$orderId])) {
                $dropshippingsByOrderId[$orderId] = [];
            }
            $dropshippingsByOrderId[$orderId][] = $dropshipping;
        }

        // Iterar sobre as notas fiscais
        if (isset($nfs['notas_fiscais']) && is_array($nfs['notas_fiscais'])) {
            foreach ($nfs['notas_fiscais'] as $nfItem) {
                if (! isset($nfItem['nota_fiscal'])) {
                    continue;
                }

                $notaFiscal = $nfItem['nota_fiscal'];
                $numeroEcommerce = (string) ($notaFiscal['numero_ecommerce'] ?? '');
                $clienteCpfCnpj = $normalizeCpfCnpj($notaFiscal['cliente']['cpf_cnpj'] ?? '');

                if (! isset($dropshippingsByOrderId[$numeroEcommerce])) {
                    continue; // Descarta se não encontrar order_id correspondente
                }

                $found = false;
                foreach ($dropshippingsByOrderId[$numeroEcommerce] as $dropshipping) {
                    $dropshippingCpfCnpj = $normalizeCpfCnpj($dropshipping['cpf_cnpj'] ?? '');

                    if ($clienteCpfCnpj === $dropshippingCpfCnpj && ! empty($clienteCpfCnpj)) {
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    $filteredInvoices[] = $nfItem;
                }
            }
        }

        return [
            'notas_fiscais' => $filteredInvoices,
        ];
    }
}

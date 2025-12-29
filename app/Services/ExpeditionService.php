<?php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Repositories\OrderBudgetRepository;
use Illuminate\Support\Facades\Auth;
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

    public function generateLabelSeparation(object $orderBudgets, object $order): array
    {
        $label = [];
        $label['carrier_name'] = null;
        $label['packer'] = Auth::user()->name;

        $maxIndex = $orderBudgets->max('order_index');
        $formattedOrderId = str_pad($orderBudgets->order_id, 5, '0', STR_PAD_LEFT);
        $label['title'] = $formattedOrderId . " - Revenda";

        if($orderBudgets->order_index === 1 && $orderBudgets->order_index < $maxIndex) {
            $label['status'] = "incompleto";
        } else if($orderBudgets->order_index > 1 && $orderBudgets->order_index < $maxIndex) {
            $label['status'] = "complemento incompleto";
        } else {
            $label['status'] = "complemento completo";
            $label['carrier_name'] = trim(explode(' - ', $order->selected_carrier_name)[0]);
        }

        $this->orderBudgetRepository->updateReadyToExpedition($orderBudgets->id);

        return $label;
    }

    /**
     * Valida se todas as notas fiscais têm o mesmo transportador
     *
     * @param array $invoiceIds Array de IDs das notas fiscais
     * @param string|null $expectedCarrier Nome do transportador esperado (opcional)
     * @return void
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
}

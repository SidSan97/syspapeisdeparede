<?php

namespace App\Services;

use App\Services\Tiny\Payloads\TinyAccountPayablePayload;
use App\Services\Tiny\Payloads\TinyExpeditionPayload;
use App\Services\Tiny\Payloads\TinyInvoicePayload;
use App\Services\Tiny\Payloads\TinyOrderPayload;
use App\Services\Tiny\TinyAccountPayableService;
use App\Services\Tiny\TinyCarrierService;
use App\Services\Tiny\TinyCustomerResolver;
use App\Services\Tiny\TinyExpeditionApiService;
use App\Services\Tiny\TinyInvoiceService;
use App\Services\Tiny\TinyOrderService;
use App\Services\Tiny\TinyProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class TinyErpService
{
    public function __construct(
        protected TinyProductService $products,
        protected TinyOrderService $orders,
        protected TinyInvoiceService $invoices,
        protected TinyExpeditionApiService $expedition,
        protected TinyAccountPayableService $accountsPayable,
        protected TinyCarrierService $carriers,
        protected TinyOrderPayload $orderPayload,
        protected TinyInvoicePayload $invoicePayload,
        protected TinyExpeditionPayload $expeditionPayload,
        protected TinyAccountPayablePayload $accountPayablePayload,
        protected TinyCustomerResolver $customers,
    ) {}

    public function searchProducts(): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao buscar produtos no Tiny ERP',
            [],
            'Erro inesperado ao buscar produtos',
            fn (): array => $this->products->searchProducts()
        );
    }

    public function getProduct(int $productId): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao buscar produto no Tiny ERP',
            ['product_id' => $productId],
            'Erro inesperado ao buscar produto',
            fn (): array => $this->products->getProduct($productId)
        );
    }

    public function getListPrice(): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao buscar lista de preços no Tiny ERP',
            [],
            'Erro inesperado ao buscar lista de preços',
            fn (): array => $this->products->getListPrice()
        );
    }

    public function getListPriceExceptions(int $listPriceId): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao buscar exceções da lista de preços no Tiny ERP',
            ['list_price_id' => $listPriceId],
            'Erro inesperado ao buscar lista de preços',
            fn (): array => $this->products->getListPriceExceptions($listPriceId)
        );
    }

    public function getCarriersTypes(): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao buscar tipos de transportadores no Tiny ERP',
            [],
            'Erro inesperado ao buscar tipos de transportadores',
            fn (): array => $this->carriers->getCarriersTypes()
        );
    }

    /**
     * Mantém a assinatura atual utilizada no projeto.
     *
     * $dropshipping contém os dados do cliente final somente quando
     * o usuário responsável pelo pedido possui is_dropshipping = true.
     *
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>|null  $dropshipping
     */
    public function sendOrder(array $order, ?array $dropshipping = null): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao enviar pedido ao Tiny ERP',
            [
                'order_id' => $order['id'] ?? null,
                'user_id' => $order['user_id'] ?? null,
            ],
            'Erro inesperado ao enviar pedido',
            fn (): array => $this->orders->send($order, $dropshipping)
        );
    }

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>|null  $dropshipping
     */
    public function sendAccountPayable(array $order, ?array $dropshipping = null): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao enviar conta a pagar ao Tiny ERP',
            [
                'order_id' => $order['id'] ?? null,
                'user_id' => $order['user_id'] ?? null,
            ],
            'Erro inesperado ao enviar conta a pagar',
            fn (): array => $this->accountsPayable->send($order, $dropshipping)
        );
    }

    public function searchInvoices($orderId = null): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao buscar notas fiscais no Tiny ERP',
            ['order_id' => $orderId],
            'Erro inesperado ao buscar notas fiscais',
            fn (): array => $this->invoices->search($orderId !== null ? (int) $orderId : null)
        );
    }

    public function searchGroupings(string $carrier): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao buscar agrupamentos de notas fiscais',
            ['carrier' => $carrier],
            'Erro inesperado ao buscar agrupamentos',
            fn (): array => $this->expedition->searchGroupings($carrier)
        );
    }

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>|null  $dropshipping
     */
    public function sendInvoice(array $order, ?array $dropshipping = null): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao enviar nota fiscal ao Tiny ERP',
            [
                'order_id' => $order['id'] ?? null,
                'user_id' => $order['user_id'] ?? null,
            ],
            'Erro inesperado ao enviar nota fiscal',
            fn (): array => $this->invoices->send($order, $dropshipping)
        );
    }

    /**
     * @param  array<string, mixed>  $invoiceData
     */
    public function issueInvoice(array $invoiceData): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao emitir nota fiscal',
            ['nf_id' => $invoiceData['nf_id'] ?? null],
            'Erro inesperado ao emitir nota fiscal',
            fn (): array => $this->invoices->issue($invoiceData)
        );
    }

    public function generateDanfe(string $id): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao obter link da DANFE',
            ['nf_id' => $id],
            'Erro inesperado ao obter link da DANFE',
            fn (): array => $this->invoices->generateDanfe($id)
        );
    }

    public function sendInvoiceToExpedition(string $nfIds, string $typeObjects): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao enviar objeto à expedição',
            [
                'nf_ids' => $nfIds,
                'type_objects' => $typeObjects,
            ],
            'Erro inesperado ao enviar objeto à expedição',
            fn (): array => $this->expedition->sendInvoiceToExpedition($nfIds, $typeObjects)
        );
    }

    public function includeGroupingInvoices(int|string $invoicesIds): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao incluir agrupamento de notas fiscais',
            ['invoice_ids' => $invoicesIds],
            'Erro inesperado ao incluir agrupamento',
            fn (): array => $this->expedition->includeGroupingInvoices($invoicesIds)
        );
    }

    public function changeExpedition(string $expeditionId, string $carrier): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao alterar expedição',
            [
                'expedition_id' => $expeditionId,
                'carrier' => $carrier,
            ],
            'Erro inesperado ao alterar expedição',
            fn (): array => $this->expedition->changeExpedition($expeditionId, $carrier)
        );
    }

    public function completeGroupingInvoices(int|string $groupingId): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao concluir agrupamento de notas fiscais',
            ['grouping_id' => $groupingId],
            'Erro inesperado ao concluir agrupamento',
            fn (): array => $this->expedition->completeGroupingInvoices($groupingId)
        );
    }

    public function printCarrierLabels(string|int $groupingId): JsonResponse|array
    {
        return $this->attempt(
            'Erro inesperado ao gerar etiquetas de impressão',
            ['grouping_id' => $groupingId],
            'Erro inesperado ao gerar etiquetas',
            fn (): array => $this->expedition->printCarrierLabels($groupingId)
        );
    }

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>|null  $dropshipping
     */
    public function makeOrder(array $order, ?array $dropshipping = null): string
    {
        return $this->orderPayload->make($order, $dropshipping);
    }

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>|null  $dropshipping
     */
    public function makeAccountPayable(array $order, ?array $dropshipping = null): string
    {
        return $this->accountPayablePayload->make($order, $dropshipping);
    }

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>|null  $dropshipping
     * @return array<string, mixed>
     */
    public function makeInvoiceData(array $order, ?array $dropshipping = null): array
    {
        return $this->invoicePayload->make($order, $dropshipping);
    }

    /**
     * @param  array<string, mixed>  $tinyErpData
     */
    public function makeExpeditionData(string $carrier, array $tinyErpData, string $expeditionId): string
    {
        return $this->expeditionPayload->make($carrier, $tinyErpData, $expeditionId);
    }

    /**
     * @param  array<string, mixed>  $customer
     * @return array<string, mixed>
     */
    public function makeClientData(array $customer): array
    {
        return $this->customers->makeClientData($customer);
    }

    /**
     * @param  array<string, mixed>  $customer
     * @return array<string, mixed>
     */
    public function makeAddressData(array $customer): array
    {
        return $this->customers->makeAddressData($customer);
    }

    public function getShippingCodeByOrigin(string $origem): ?string
    {
        return $this->carriers->shippingCodeByOrigin($origem);
    }

    public function getCarrierId(string $carrier): ?string
    {
        return $this->carriers->carrierId($carrier);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  callable(): array<string, mixed>  $callback
     */
    private function attempt(string $logMessage, array $context, string $clientMessage, callable $callback): JsonResponse|array
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            Log::error($logMessage, [
                ...$context,
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return response()->json([
                'success' => false,
                'message' => $clientMessage.': '.$e->getMessage(),
            ], 500);
        }
    }
}

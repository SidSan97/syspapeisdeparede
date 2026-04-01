<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderPaymentLink;
use App\Services\OrderPaymentStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Recebe notificações do Pagar.me (order.paid, etc).
     */
    public function handlePagarme(Request $request): JsonResponse
    {
        $payload = $request->all();
        $type = $payload['type'] ?? null;

        if ($type === 'order.paid') {
            $this->handleOrderPaid($payload);
        }

        return response()->json(['received' => true], 200);
    }

    protected function handleOrderPaid(array $payload): void
    {
        $data = $payload['data'] ?? [];
        $pagarmeOrderId = $data['id'] ?? null;
        $status = $data['status'] ?? null;

        if (! $pagarmeOrderId || $status !== 'paid') {
            return;
        }

        $orderId = $this->resolveOrderId($data);

        if (! $orderId) {
            Log::warning('Webhook Pagar.me order.paid: não foi possível identificar o order_id', [
                'pagarme_order_id' => $pagarmeOrderId,
                'data_keys' => array_keys($data),
            ]);

            return;
        }

        $order = Order::find($orderId);

        if (! $order) {
            Log::warning('Webhook Pagar.me order.paid: Order não encontrado', [
                'order_id' => $orderId,
                'pagarme_order_id' => $pagarmeOrderId,
            ]);

            return;
        }

        $paymentLink = $this->resolvePaymentLink($order->id, $data);

        if ($paymentLink && $paymentLink->status !== 'paid') {
            $paymentLink->update([
                'status' => 'paid',
                'paid_at' => now(),
                'provider_payload' => $data,
            ]);
        }

        $paymentState = app(OrderPaymentStateService::class);
        $paymentState->refreshPaidFlags($order);

        if ($paymentLink) {
            $paymentLink->refresh();
            if ($paymentLink->status === 'paid' && $paymentState->linkContainsArtes($paymentLink)) {
                $paymentState->syncBudgetsAfterArtesPaid($order);
            }
        }

        Log::info('Webhook Pagar.me: pedido marcado como pago', [
            'order_id' => $orderId,
            'pagarme_order_id' => $pagarmeOrderId,
            'payment_link_id' => $paymentLink?->id,
        ]);
    }

    /**
     * Tenta obter o ID do Order a partir do payload.
     */
    protected function resolveOrderId(array $data): ?int
    {
        $metadata = $data['metadata'] ?? [];
        if (is_array($metadata) && ! empty($metadata['order_id'])) {
            $id = (int) $metadata['order_id'];
            return $id > 0 ? $id : null;
        }

        $items = $data['items'] ?? [];
        foreach ($items as $item) {
            $desc = (string) ($item['description'] ?? '');
            if (preg_match('/\[order_ref:(\d+)\]/', $desc, $m)) {
                return (int) $m[1];
            }
        }

        return null;
    }

    protected function resolvePaymentLink(int $orderId, array $data): ?OrderPaymentLink
    {
        $externalOrderId = $data['id'] ?? null;
        if ($externalOrderId) {
            $link = OrderPaymentLink::query()
                ->where('order_id', $orderId)
                ->where('external_order_id', (string) $externalOrderId)
                ->first();

            if ($link) {
                return $link;
            }
        }

        $metadata = $data['metadata'] ?? [];
        if (is_array($metadata) && ! empty($metadata['internal_payment_link_id'])) {
            return OrderPaymentLink::query()
                ->where('order_id', $orderId)
                ->find((int) $metadata['internal_payment_link_id']);
        }

        // conciliar por componentes e valor do payload para evitar baixa no link errado.
        $metadataComponents = $this->extractComponentsFromMetadata($metadata);
        $payloadAmount = isset($data['amount']) ? round(((float) $data['amount']) / 100, 2) : null;

        if (!empty($metadataComponents) || $payloadAmount !== null) {
            $pendingLinks = OrderPaymentLink::query()
                ->where('order_id', $orderId)
                ->where('status', 'pending')
                ->get();

            $matches = $pendingLinks->filter(function (OrderPaymentLink $link) use ($metadataComponents, $payloadAmount) {
                $componentsMatch = true;
                if (!empty($metadataComponents)) {
                    $linkComponents = $this->normalizeComponentsArray($link->components ?? []);
                    $componentsMatch = $linkComponents === $metadataComponents;
                }

                $amountMatch = true;
                if ($payloadAmount !== null) {
                    $linkAmount = round((float) ($link->amount_total ?? 0), 2);
                    $amountMatch = abs($linkAmount - $payloadAmount) < 0.01;
                }

                return $componentsMatch && $amountMatch;
            });

            if ($matches->isNotEmpty()) {
                return $matches->sortByDesc('id')->first();
            }
        }

        return OrderPaymentLink::query()
            ->where('order_id', $orderId)
            ->where('status', 'pending')
            ->latest('id')
            ->first();
    }

    protected function extractComponentsFromMetadata($metadata): array
    {
        if (!is_array($metadata) || empty($metadata['components'])) {
            return [];
        }

        $raw = $metadata['components'];
        if (is_array($raw)) {
            return $this->normalizeComponentsArray($raw);
        }

        if (is_string($raw)) {
            $items = array_map('trim', explode(',', $raw));
            return $this->normalizeComponentsArray($items);
        }

        return [];
    }

    protected function normalizeComponentsArray(array $components): array
    {
        $normalized = array_map(static fn ($item) => strtoupper(trim((string) $item)), $components);
        $normalized = array_values(array_filter($normalized, static fn ($item) => $item !== ''));
        sort($normalized);

        return $normalized;
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderPaymentLink;
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

        $this->refreshOrderPaymentStatus($order);

        Log::info('Webhook Pagar.me: pedido marcado como pago', [
            'order_id' => $orderId,
            'pagarme_order_id' => $pagarmeOrderId,
            'payment_link_id' => $paymentLink?->id,
        ]);
    }

    /**
     * Tenta obter o ID do nosso Order a partir do payload.
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

        return OrderPaymentLink::query()
            ->where('order_id', $orderId)
            ->where('status', 'pending')
            ->latest('id')
            ->first();
    }

    protected function refreshOrderPaymentStatus(Order $order): void
    {
        $composition = app(\App\Services\OrderPaymentCompositionService::class)
            ->getOrderComposition($order);

        $orderTotal = (float) ($order->payment_method === 'pix'
            ? ($composition['TOTAL_PIX'] ?? 0)
            : ($composition['TOTAL_CREDIT_CARD'] ?? 0));

        $paidAmount = (float) OrderPaymentLink::query()
            ->where('order_id', $order->id)
            ->where('status', 'paid')
            ->sum('amount_total');

        $isPaid = $paidAmount >= $orderTotal && $orderTotal > 0;
        $paymentStatus = $paidAmount <= 0 ? 'unpaid' : ($isPaid ? 'paid' : 'partial');

        $order->update([
            'paid' => $isPaid ? 1 : 0,
            'payment_status' => $paymentStatus,
        ]);
    }
}

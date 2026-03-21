<?php

namespace App\Http\Controllers;

use App\Models\Order;
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

        if ($order->paid) {
            return; // Idempotência: já processado
        }

        $order->update(['paid' => 1]);

        Log::info('Webhook Pagar.me: pedido marcado como pago', [
            'order_id' => $orderId,
            'pagarme_order_id' => $pagarmeOrderId,
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
}

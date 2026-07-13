<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderPaymentLink;
use App\Repositories\OrderBudgetRepository;
use App\Services\OrderPaymentStateService;
use App\Services\TinyErpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected $tinyErpService;
    protected $orderBudgetRepository;

    public function __construct(TinyErpService $tinyErpService, OrderBudgetRepository $orderBudgetRepository)
    {
        $this->tinyErpService = $tinyErpService;
        $this->orderBudgetRepository = $orderBudgetRepository;
    }

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

        if (($data['status'] ?? null) !== 'paid') {
            return;
        }

        $paidCharge = $this->resolvePaidCharge($data);

        if (! $paidCharge) {
            return;
        }

        $orderId = $this->resolveOrderId($data);

        if (! $orderId) {
            Log::warning('Webhook Pagar.me order.paid: não foi possível identificar o order_id', [
                'pagarme_order_id' => $data['id'] ?? null,
                'charge_code' => $paidCharge['code'] ?? null,
                'data_keys' => array_keys($data),
            ]);

            return;
        }

        $order = Order::find($orderId);

        if (! $order) {
            Log::warning('Webhook Pagar.me order.paid: Order não encontrado', [
                'order_id' => $orderId,
                'pagarme_order_id' => $data['id'] ?? null,
                'charge_code' => $paidCharge['code'] ?? null,
            ]);

            return;
        }

        $paymentLink = $this->resolvePaymentLink($order->id, $data, $paidCharge);

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

        $orderTiny = $this->tinyErpService->sendOrder($order->toArray(), $order->dropshipping_budget->toArray());
        $this->orderBudgetRepository->updateTinyErpOrderId($order->id, $orderTiny['registros']['registro']['id']);

        Log::info('Webhook Pagar.me: pedido marcado como pago', [
            'order_id' => $orderId,
            'pagarme_order_id' => $data['id'] ?? null,
            'charge_code' => $paidCharge['code'] ?? null,
            'payment_link_id' => $paymentLink?->id,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function resolvePaidCharge(array $data): ?array
    {
        foreach ($data['charges'] ?? [] as $charge) {
            if (! is_array($charge)) {
                continue;
            }

            if (($charge['status'] ?? null) === 'paid') {
                return $charge;
            }
        }

        return null;
    }

    /**
     * Extrai o ID do pedido local a partir de items[].description (ex.: "Pedido #121 - ARTES").
     */
    protected function resolveOrderId(array $data): ?int
    {
        foreach ($data['items'] ?? [] as $item) {
            $description = (string) ($item['description'] ?? '');

            if (preg_match('/#(\d+)\s*-/', $description, $matches)) {
                $id = (int) $matches[1];

                if ($id > 0) {
                    return $id;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $paidCharge
     */
    protected function resolvePaymentLink(int $orderId, array $data, array $paidCharge): ?OrderPaymentLink
    {
        $chargeCode = $paidCharge['code'] ?? null;

        if ($chargeCode) {
            $link = OrderPaymentLink::query()
                ->where('order_id', $orderId)
                ->where(function ($query) use ($chargeCode) {
                    $query->where('external_order_id', (string) $chargeCode)
                        ->orWhere('external_payment_link_id', (string) $chargeCode);
                })
                ->first();

            if ($link) {
                return $link;
            }
        }

        $descriptionComponents = $this->extractComponentsFromDescription($data);
        $chargeAmount = isset($paidCharge['amount'])
            ? round(((float) $paidCharge['amount']) / 100, 2)
            : (isset($data['amount']) ? round(((float) $data['amount']) / 100, 2) : null);

        if (! empty($descriptionComponents) || $chargeAmount !== null) {
            $pendingLinks = OrderPaymentLink::query()
                ->where('order_id', $orderId)
                ->where('status', 'pending')
                ->get();

            $matches = $pendingLinks->filter(function (OrderPaymentLink $link) use ($descriptionComponents, $chargeAmount) {
                $componentsMatch = true;

                if (! empty($descriptionComponents)) {
                    $linkComponents = $this->normalizeComponentsArray($link->components ?? []);
                    $componentsMatch = $linkComponents === $descriptionComponents;
                }

                $amountMatch = true;

                if ($chargeAmount !== null) {
                    $linkAmount = round((float) ($link->amount_total ?? 0), 2);
                    $amountMatch = abs($linkAmount - $chargeAmount) < 0.01;
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

    /**
     * @return list<string>
     */
    protected function extractComponentsFromDescription(array $data): array
    {
        $components = [];

        foreach ($data['items'] ?? [] as $item) {
            $description = (string) ($item['description'] ?? '');

            if (preg_match('/#\d+\s*-\s*(.+)$/', $description, $matches)) {
                $raw = trim($matches[1]);

                if ($raw !== '') {
                    $components = array_merge($components, array_map('trim', explode('+', $raw)));
                }
            }
        }

        return $this->normalizeComponentsArray($components);
    }

    protected function normalizeComponentsArray(array $components): array
    {
        $normalized = array_map(static fn ($item) => strtoupper(trim((string) $item)), $components);
        $normalized = array_values(array_filter($normalized, static fn ($item) => $item !== ''));
        sort($normalized);

        return $normalized;
    }

}

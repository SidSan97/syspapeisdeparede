<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderPaymentLink;
use App\Repositories\OrderBudgetRepository;
use App\Services\OrderPaymentStateService;
use App\Services\TinyErpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class WebhookController extends Controller
{
    protected $tinyErpService;

    protected $orderBudgetRepository;

    public function __construct(
        TinyErpService $tinyErpService,
        OrderBudgetRepository $orderBudgetRepository
    ) {
        $this->tinyErpService = $tinyErpService;
        $this->orderBudgetRepository = $orderBudgetRepository;
    }

    /**
     * Recebe notificações do Pagar.me.
     */
    public function handlePagarme(Request $request): JsonResponse
    {
        // Pagar.me envia JSON.
        // O fallback mantém compatibilidade caso o Content-Type venha diferente.
        $payload = $request->json()->all();

        if (empty($payload)) {
            $payload = $request->all();
        }

        $webhookId = $payload['id'] ?? null;
        $type = $payload['type'] ?? null;

        Log::info('Webhook Pagar.me recebido', [
            'webhook_id' => $webhookId,
            'type' => $type,
            'pagarme_order_id' => $payload['data']['id'] ?? null,
            'payment_link_id' => $payload['data']['metadata']['payment_link_id'] ?? null,
        ]);

        if (! $type) {
            Log::warning('Webhook Pagar.me sem tipo de evento', [
                'webhook_id' => $webhookId,
            ]);

            return response()->json([
                'received' => false,
                'error' => 'Missing webhook type',
            ], 400);
        }

        /*
         * Eventos não utilizados pela aplicação são confirmados normalmente.
         */
        if ($type !== 'order.paid') {
            Log::info('Webhook Pagar.me ignorado', [
                'webhook_id' => $webhookId,
                'type' => $type,
            ]);

            return response()->json([
                'received' => true,
                'ignored' => true,
            ], 200);
        }

        try {
            $this->handleOrderPaid($payload);

            return response()->json([
                'received' => true,
            ], 200);
        } catch (Throwable $e) {
            Log::error('Erro ao processar webhook Pagar.me', [
                'webhook_id' => $webhookId,
                'type' => $type,
                'pagarme_order_id' => $payload['data']['id'] ?? null,
                'payment_link_id' => $payload['data']['metadata']['payment_link_id'] ?? null,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            /*
             * Não confirme como processado algo que não conseguimos
             * efetivamente processar.
             */
            return response()->json([
                'received' => false,
            ], 500);
        }
    }

    protected function handleOrderPaid(array $payload): void
    {
        $data = $payload['data'] ?? [];
        $webhookId = $payload['id'] ?? null;

        if (($data['status'] ?? null) !== 'paid') {
            Log::warning('Webhook order.paid recebido com status diferente de paid', [
                'webhook_id' => $webhookId,
                'pagarme_order_id' => $data['id'] ?? null,
                'status' => $data['status'] ?? null,
            ]);

            return;
        }

        $paidCharge = $this->resolvePaidCharge($data);

        if (! $paidCharge) {
            throw new RuntimeException(
                'Webhook order.paid não possui uma charge com status paid.'
            );
        }

        $orderId = $this->resolveOrderId($data, $paidCharge);

        if (! $orderId) {
            Log::warning('Webhook Pagar.me: não foi possível identificar o pedido local', [
                'webhook_id' => $webhookId,
                'pagarme_order_id' => $data['id'] ?? null,
                'references' => $this->resolveExternalReferences($data, $paidCharge),
            ]);

            throw new RuntimeException(
                'Não foi possível relacionar o webhook a um pedido local.'
            );
        }

        $paymentLink = $this->resolvePaymentLink(
            $orderId,
            $data,
            $paidCharge
        );

        if (! $paymentLink) {
            Log::warning('Webhook Pagar.me: payment link não encontrado', [
                'webhook_id' => $webhookId,
                'order_id' => $orderId,
                'pagarme_order_id' => $data['id'] ?? null,
                'references' => $this->resolveExternalReferences($data, $paidCharge),
            ]);

            throw new RuntimeException(
                'Não foi possível relacionar o webhook a um payment link local.'
            );
        }

        DB::transaction(function () use (
            $orderId,
            $paymentLink,
            $data,
            $paidCharge,
            $webhookId
        ) {
            $order = Order::query()
                ->lockForUpdate()
                ->find($orderId);

            if (! $order) {
                throw new RuntimeException(
                    "Pedido local {$orderId} não encontrado."
                );
            }

            $paymentLink = OrderPaymentLink::query()
                ->whereKey($paymentLink->getKey())
                ->lockForUpdate()
                ->first();

            if (! $paymentLink) {
                throw new RuntimeException(
                    'Payment link local deixou de existir durante o processamento.'
                );
            }

            $alreadyPaid = $paymentLink->status === 'paid';

            if (! $alreadyPaid) {
                $paymentLink->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'provider_payload' => $data,
                ]);

                Log::info('Payment link marcado como pago', [
                    'webhook_id' => $webhookId,
                    'order_id' => $orderId,
                    'payment_link_id' => $paymentLink->id,
                    'pagarme_order_id' => $data['id'] ?? null,
                    'charge_id' => $paidCharge['id'] ?? null,
                    'charge_code' => $paidCharge['code'] ?? null,
                ]);
            } else {
                Log::info('Webhook Pagar.me já havia sido processado', [
                    'webhook_id' => $webhookId,
                    'order_id' => $orderId,
                    'payment_link_id' => $paymentLink->id,
                ]);
            }

            $paymentState = app(OrderPaymentStateService::class);

            /*
             * Mantemos o refresh mesmo em reenvios.
             *
             * Isso ajuda a recuperar um cenário em que o payment link
             * tenha sido marcado como pago, mas algum processamento
             * posterior tenha falhado.
             */
            $paymentState->refreshPaidFlags($order);

            $paymentLink->refresh();

            if (
                $paymentLink->status === 'paid'
                && $paymentState->linkContainsArtes($paymentLink)
            ) {
                $paymentState->syncBudgetsAfterArtesPaid($order);
            }

            Log::info('Webhook Pagar.me processado com sucesso', [
                'webhook_id' => $webhookId,
                'order_id' => $orderId,
                'payment_link_id' => $paymentLink->id,
                'pagarme_order_id' => $data['id'] ?? null,
                'charge_id' => $paidCharge['id'] ?? null,
                'charge_code' => $paidCharge['code'] ?? null,
                'already_paid' => $alreadyPaid,
            ]);
        });
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
     * Identifica todas as referências externas que podem relacionar
     * o pedido do Pagar.me ao payment link local.
     *
     * @return list<string>
     */
    protected function resolveExternalReferences(
        array $data,
        array $paidCharge = []
    ): array {
        $references = [
            // Referências semanticamente mais fortes.
            $data['metadata']['payment_link_id'] ?? null,
            $paidCharge['metadata']['payment_link_id'] ?? null,

            // Checkout / integração.
            $data['integration']['code'] ?? null,

            // No payload atual também contém o payment link.
            $data['code'] ?? null,
            $paidCharge['code'] ?? null,

            // Verdadeiro ID do pedido Pagar.me (or_...).
            $data['id'] ?? null,
        ];

        $references = array_filter(
            $references,
            static fn ($value) => is_string($value) && $value !== ''
        );

        return array_values(array_unique($references));
    }

    /**
     * Extrai o ID do pedido local usando as referências enviadas
     * pelo Pagar.me.
     */
    protected function resolveOrderId(
        array $data,
        array $paidCharge
    ): ?int {
        $references = $this->resolveExternalReferences(
            $data,
            $paidCharge
        );

        if (empty($references)) {
            return null;
        }

        $orderId = OrderPaymentLink::query()
            ->where(function ($query) use ($references) {
                $query
                    ->whereIn('external_payment_link_id', $references)
                    ->orWhereIn('external_order_id', $references);
            })
            ->value('order_id');

        return $orderId !== null
            ? (int) $orderId
            : null;
    }

    /**
     * @param  array<string, mixed>  $paidCharge
     */
    protected function resolvePaymentLink(
        int $orderId,
        array $data,
        array $paidCharge
    ): ?OrderPaymentLink {
        /*
         * 1. Primeiro tenta resolver por identificadores externos.
         */
        $references = $this->resolveExternalReferences(
            $data,
            $paidCharge
        );

        if (! empty($references)) {
            $link = OrderPaymentLink::query()
                ->where('order_id', $orderId)
                ->where(function ($query) use ($references) {
                    $query
                        ->whereIn('external_payment_link_id', $references)
                        ->orWhereIn('external_order_id', $references);
                })
                ->first();

            if ($link) {
                return $link;
            }
        }

        /*
         * 2. Fallback por componentes + valor.
         *
         * Este fallback somente é aceito se houver exatamente
         * uma correspondência, evitando marcar o link errado.
         */
        $descriptionComponents = $this->extractComponentsFromDescription($data);

        $chargeAmount = isset($paidCharge['amount'])
            ? round(((float) $paidCharge['amount']) / 100, 2)
            : (
                isset($data['amount'])
                    ? round(((float) $data['amount']) / 100, 2)
                    : null
            );

        if (empty($descriptionComponents) && $chargeAmount === null) {
            return null;
        }

        $pendingLinks = OrderPaymentLink::query()
            ->where('order_id', $orderId)
            ->where('status', 'pending')
            ->get();

        $matches = $pendingLinks->filter(
            function (OrderPaymentLink $link) use (
                $descriptionComponents,
                $chargeAmount
            ) {
                $componentsMatch = true;

                if (! empty($descriptionComponents)) {
                    $linkComponents = $this->normalizeComponentsArray(
                        $link->components ?? []
                    );

                    $componentsMatch =
                        $linkComponents === $descriptionComponents;
                }

                $amountMatch = true;

                if ($chargeAmount !== null) {
                    $linkAmount = round(
                        (float) ($link->amount_total ?? 0),
                        2
                    );

                    $amountMatch =
                        abs($linkAmount - $chargeAmount) < 0.01;
                }

                return $componentsMatch && $amountMatch;
            }
        );

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1) {
            Log::warning(
                'Webhook Pagar.me: mais de um payment link corresponde ao pagamento',
                [
                    'order_id' => $orderId,
                    'matching_payment_link_ids' => $matches
                        ->pluck('id')
                        ->values()
                        ->all(),
                    'charge_amount' => $chargeAmount,
                    'components' => $descriptionComponents,
                ]
            );
        }

        /*
         * Não escolher simplesmente o último payment link pendente.
         * Isso pode atribuir um pagamento ao link errado.
         */
        return null;
    }

    /**
     * @return list<string>
     */
    protected function extractComponentsFromDescription(
        array $data
    ): array {
        $components = [];

        foreach ($data['items'] ?? [] as $item) {
            $description = (string) ($item['description'] ?? '');

            if (
                preg_match(
                    '/#\d+\s*-\s*(.+)$/',
                    $description,
                    $matches
                )
            ) {
                $raw = trim($matches[1]);

                if ($raw !== '') {
                    $components = array_merge(
                        $components,
                        array_map(
                            'trim',
                            explode('+', $raw)
                        )
                    );
                }
            }
        }

        return $this->normalizeComponentsArray($components);
    }

    /**
     * @return list<string>
     */
    protected function normalizeComponentsArray(
        mixed $components
    ): array {
        /*
         * Também suporta o caso de o model não possuir cast
         * e retornar o JSON do banco como string.
         */
        if (is_string($components)) {
            $decoded = json_decode($components, true);

            $components = is_array($decoded)
                ? $decoded
                : [];
        }

        if (! is_array($components)) {
            return [];
        }

        $normalized = array_map(
            static fn ($item) => strtoupper(
                trim((string) $item)
            ),
            $components
        );

        $normalized = array_values(
            array_filter(
                $normalized,
                static fn ($item) => $item !== ''
            )
        );

        sort($normalized);

        return $normalized;
    }
}

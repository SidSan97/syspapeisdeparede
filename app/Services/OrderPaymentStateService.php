<?php

namespace App\Services;

use App\Models\BudgetWall;
use App\Models\Order;
use App\Models\OrderPaymentLink;
use App\Repositories\DropshippingRepository;
use App\Repositories\OrderBudgetRepository;
use App\Support\OrderBudgetStatus;

class OrderPaymentStateService
{
    public function __construct(
        protected OrderPaymentCompositionService $compositionService,
        protected TinyErpService $tinyErpService,
        protected OrderBudgetRepository $orderBudgetRepository,
        protected DropshippingRepository $dropshippingRepository
    ) {}

    /**
     * Desfaz o estado de pago do pedido (ex.: após edição estrutural).
     * Mantém o histórico dos links, mas marca como pendente para nova quitação.
     */
    public function markAsUnpaidPending(Order $order): void
    {
        OrderPaymentLink::query()
            ->where('order_id', $order->id)
            ->where('status', 'paid')
            ->update([
                'status' => 'pending',
                'paid_at' => null,
            ]);

        $order->update([
            'paid' => 0,
            'payment_status' => 'unpaid',
            'flags' => 'Aguardando pagamento',
        ]);
    }

    /**
     * Recalcula paid / payment_status do pedido a partir dos links pagos.
     */
    public function refreshPaidFlags(Order $order): void
    {
        $order->refresh();
        $dropshipping = null;

        $composition = $this->compositionService->getOrderComposition($order);

        $orderTotal = (float) ($order->payment_method === 'pix'
            ? ($composition['TOTAL_PIX'] ?? 0)
            : ($composition['TOTAL_CREDIT_CARD'] ?? 0));

        $paidAmount = (float) OrderPaymentLink::query()
            ->where('order_id', $order->id)
            ->where('status', 'paid')
            ->sum('amount_total');

        if ($order->dropshipping_budget) {
            $dropshipping = $this->dropshippingRepository->findDropshippingByOrderId($order->id);
        }

        $isPaid = $paidAmount >= $orderTotal && $orderTotal > 0;
        $paymentStatus = $paidAmount <= 0 ? 'unpaid' : ($isPaid ? 'paid' : 'partial');

        $order->update([
            'paid' => $isPaid ? 1 : 0,
            'payment_status' => $paymentStatus,
        ]);

        $orderTiny = $this->tinyErpService->sendOrder($order->toArray(), $dropshipping?->toArray());

        if (isset($orderTiny['registros']['registro']['id'])) {
            $this->orderBudgetRepository->updateTinyErpOrderId($order->id, $orderTiny['registros']['registro']['id']);
        }
    }

    public function linkContainsArtes(OrderPaymentLink $link): bool
    {
        $components = $link->components ?? [];

        return is_array($components) && in_array('ARTES', $components, true);
    }

    /**
     * Quando o pagamento inclui artes, atualiza status dos cards (OrderBudget):
     * - modelo com request_art_on_payment: Aguardando Arte;
     * - modelo sem exigência de link (ou sem modelo): Arte Recebida;
     * - modelo com request_link: Arte Recebida se link_referring_model preenchido, senão Aguardando Arte.
     */
    public function syncBudgetsAfterArtesPaid(Order $order): void
    {
        $order->loadMissing(['orderBudgets.wall.collectionModel']);

        foreach ($order->orderBudgets as $orderBudget) {
            $wall = $orderBudget->wall;
            if (! $wall instanceof BudgetWall) {
                continue;
            }

            $model = $wall->collectionModel;

            if ($model?->request_art_on_payment) {
                if ($orderBudget->status != OrderBudgetStatus::WAITING_ART) {
                    $orderBudget->update(['status' => OrderBudgetStatus::WAITING_ART]);
                }

                continue;
            }

            if (! $model || ! $model->request_link) {
                if ($orderBudget->status != OrderBudgetStatus::ART_RECEIVED) {
                    $orderBudget->update(['status' => OrderBudgetStatus::ART_RECEIVED]);
                }

                continue;
            }

            $newStatus = $this->wallHasReferringLink($wall)
                ? OrderBudgetStatus::ART_RECEIVED
                : OrderBudgetStatus::WAITING_ART;

            if ($orderBudget->status != $newStatus) {
                $orderBudget->update(['status' => $newStatus]);
            }
        }
    }

    protected function wallHasReferringLink(BudgetWall $wall): bool
    {
        $link = $wall->link_referring_model;

        return $link !== null && trim((string) $link) !== '';
    }
}

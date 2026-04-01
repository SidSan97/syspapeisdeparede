<?php

namespace App\Services;

use App\Models\BudgetWall;
use App\Models\Order;
use App\Models\OrderPaymentLink;

class OrderPaymentStateService
{
    public function __construct(
        protected OrderPaymentCompositionService $compositionService
    ) {}

    /**
     * Recalcula paid / payment_status do pedido a partir dos links pagos.
     */
    public function refreshPaidFlags(Order $order): void
    {
        $order->refresh();

        $composition = $this->compositionService->getOrderComposition($order);

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

    public function linkContainsArtes(OrderPaymentLink $link): bool
    {
        $components = $link->components ?? [];

        return is_array($components) && in_array('ARTES', $components, true);
    }

    /**
     * Quando o pagamento inclui artes, cards (OrderBudget) com modelo que exige link de referência
     * recebem status conforme a parede já tem ou não link_referring_model preenchido.
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
            if (! $model || ! $model->request_link) {
                continue;
            }

            $newStatus = $this->wallHasReferringLink($wall) ? 'Arte Recebida' : 'Aguardando Arte';

            if ($orderBudget->status !== $newStatus) {
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

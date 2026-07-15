<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPaymentLink;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class OrderEditWalletCreditService
{
    public function __construct(
        protected OrderPaymentCompositionService $compositionService
    ) {}

    /**
     * Captura composição antes da edição de ambientes/paredes quando o pedido está totalmente pago
     * ou quando a linha de PRODUTOS já foi quitada por links pagos (pedido parcial).
     */
    public function shouldSnapshotCompositionForRoomEdit(Order $order): bool
    {
        if ((int) $order->paid === 1) {
            return true;
        }

        return $this->hasProductsLineCoveredByPaidLinks($order);
    }

    /**
     * Quando a composição diminui após edição (área/modelos/frete), credita o usuário do pedido.
     * Pedido totalmente pago: artes + produtos + frete como antes.
     * Pedido parcial com produtos já pagos: apenas crédito proporcional a PRODUTOS.
     */
    public function creditIfCompositionDecreased(Order $order, array $compositionBefore): void
    {
        if (! $order->user_id) {
            return;
        }

        $after = $this->compositionService->getOrderComposition($order);
        $fullyPaidOrder = (int) $order->paid === 1;

        $oldPix = (float) $compositionBefore['PRODUTOS_PIX'];
        $newPix = (float) $after['PRODUTOS_PIX'];
        $oldCc = (float) $compositionBefore['PRODUTOS_CREDIT_CARD'];
        $newCc = (float) $after['PRODUTOS_CREDIT_CARD'];

        $dPix = round(max(0.0, $oldPix - $newPix), 2);
        $dCc = round(max(0.0, $oldCc - $newCc), 2);

        $paidProdutos = $this->sumPaidProdutos($order->id);

        $credit = 0.0;
        $parts = [];

        if ($fullyPaidOrder) {
            $isPix = $order->payment_method === 'pix';
            $oldProd = $isPix ? $oldPix : $oldCc;
            $newProd = $isPix ? $newPix : $newCc;
            $dProd = round(max(0.0, $oldProd - $newProd), 2);

            $dArtes = round(max(0.0, (float) $compositionBefore['ARTES'] - (float) $after['ARTES']), 2);
            $dFrete = round(max(0.0, (float) $compositionBefore['FRETE'] - (float) $after['FRETE']), 2);
            $credit = round($dArtes + $dFrete + $dProd, 2);

            $parts = array_filter([
                $dArtes >= 0.01 ? sprintf('ARTES R$ %s', number_format($dArtes, 2, ',', '.')) : null,
                $dProd >= 0.01 ? sprintf('Produtos R$ %s', number_format($dProd, 2, ',', '.')) : null,
                $dFrete >= 0.01 ? sprintf('Frete R$ %s', number_format($dFrete, 2, ',', '.')) : null,
            ]);
        } else {
            $credit = $this->computePartialOrderProductsCredit(
                $paidProdutos,
                $oldPix,
                $newPix,
                $dPix,
                $oldCc,
                $newCc,
                $dCc
            );
            if ($credit < 0.01) {
                return;
            }
            $parts = [sprintf('Produtos R$ %s', number_format($credit, 2, ',', '.'))];
        }

        if ($credit < 0.01) {
            return;
        }

        $description = 'Crédito por revisão do pedido #'.$order->id;
        if ($parts !== []) {
            $description .= ' ('.implode(', ', $parts).')';
        }

        $this->creditUserWallet((int) $order->user_id, $credit, $description);
    }

    protected function hasProductsLineCoveredByPaidLinks(Order $order): bool
    {
        $paid = $this->sumPaidProdutos($order->id);
        if ($paid < 0.01) {
            return false;
        }

        $composition = $this->compositionService->getOrderComposition($order);
        $pixLine = (float) $composition['PRODUTOS_PIX'];
        $ccLine = (float) $composition['PRODUTOS_CREDIT_CARD'];

        return ($paid + 0.005 >= $pixLine) || ($paid + 0.005 >= $ccLine);
    }

    protected function sumPaidProdutos(int $orderId): float
    {
        return (float) OrderPaymentLink::query()
            ->where('order_id', $orderId)
            ->where('status', 'paid')
            ->sum('amount_produtos');
    }

    /**
     * Uma única piscina amount_produtos; se à vista e a prazo estão cobertos, usa o menor crédito calculado.
     */
    protected function computePartialOrderProductsCredit(
        float $paidProdutos,
        float $oldPix,
        float $newPix,
        float $dPix,
        float $oldCc,
        float $newCc,
        float $dCc
    ): float {
        $eligiblePix = ($paidProdutos + 0.005 >= $oldPix) && $dPix >= 0.01;
        $eligibleCc = ($paidProdutos + 0.005 >= $oldCc) && $dCc >= 0.01;

        $cPix = $eligiblePix
            ? round(min($dPix, max(0.0, $paidProdutos - $newPix)), 2)
            : 0.0;
        $cCc = $eligibleCc
            ? round(min($dCc, max(0.0, $paidProdutos - $newCc)), 2)
            : 0.0;

        if ($eligiblePix && $eligibleCc) {
            return round(min($cPix, $cCc), 2);
        }

        if ($eligiblePix) {
            return $cPix;
        }

        return $cCc;
    }

    protected function creditUserWallet(int $userId, float $amount, string $description): void
    {
        DB::transaction(function () use ($userId, $amount, $description) {
            UserWallet::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0]
            );

            $wallet = UserWallet::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            $newBalance = round((float) $wallet->balance + $amount, 2);
            $wallet->update(['balance' => $newBalance]);

            WalletTransaction::create([
                'user_wallet_id' => $wallet->id,
                'amount' => round($amount, 2),
                'balance_after' => $newBalance,
                'type' => 'order_edit_credit',
                'description' => $description,
            ]);
        });
    }
}

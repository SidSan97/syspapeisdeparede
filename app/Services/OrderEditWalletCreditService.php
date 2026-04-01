<?php

namespace App\Services;

use App\Models\Order;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class OrderEditWalletCreditService
{
    public function __construct(
        protected OrderPaymentCompositionService $compositionService
    ) {}

    /**
     * Quando um pedido já pago é editado e a composição (arte, produtos ou frete) fica menor,
     * credita no saldo do usuário do pedido a soma das reduções.
     */
    public function creditIfCompositionDecreased(Order $order, array $compositionBefore): void
    {
        if ((int) $order->paid !== 1 || ! $order->user_id) {
            return;
        }

        $after = $this->compositionService->getOrderComposition($order);

        $isPix = $order->payment_method === 'pix';
        $oldProd = $isPix ? (float) $compositionBefore['PRODUTOS_PIX'] : (float) $compositionBefore['PRODUTOS_CREDIT_CARD'];
        $newProd = $isPix ? (float) $after['PRODUTOS_PIX'] : (float) $after['PRODUTOS_CREDIT_CARD'];

        $dArtes = round(max(0.0, (float) $compositionBefore['ARTES'] - (float) $after['ARTES']), 2);
        $dFrete = round(max(0.0, (float) $compositionBefore['FRETE'] - (float) $after['FRETE']), 2);
        $dProd = round(max(0.0, $oldProd - $newProd), 2);

        $credit = round($dArtes + $dFrete + $dProd, 2);

        if ($credit < 0.01) {
            return;
        }

        $parts = array_filter([
            $dArtes > 0 ? sprintf('ARTES R$ %s', number_format($dArtes, 2, ',', '.')) : null,
            $dProd > 0 ? sprintf('Produtos R$ %s', number_format($dProd, 2, ',', '.')) : null,
            $dFrete > 0 ? sprintf('Frete R$ %s', number_format($dFrete, 2, ',', '.')) : null,
        ]);

        $description = 'Crédito por revisão do pedido #'.$order->id;
        if ($parts !== []) {
            $description .= ' ('.implode(', ', $parts).')';
        }

        $this->creditUserWallet((int) $order->user_id, $credit, $description);
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

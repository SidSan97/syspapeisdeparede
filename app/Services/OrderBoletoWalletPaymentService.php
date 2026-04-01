<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPaymentLink;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class OrderBoletoWalletPaymentService
{
    public function __construct(
        protected OrderPaymentCompositionService $compositionService,
        protected OrderPaymentStateService $orderPaymentState
    ) {}

    /**
     * Quita componentes selecionados usando saldo da carteira, com valores à prazo (como cartão),
     * pagamento integral em uma única vez (sem parcelas e sem link Pagar.me).
     */
    public function payFromWallet(Order $order, array $components, User $payer): void
    {
        if (in_array('FRETE', $components, true)) {
            abort(422, 'O frete só pode ser pago via Pix.');
        }

        $calculation = $this->compositionService->calculateSelectedAmount(
            $order,
            $components,
            'credit_card'
        );

        $total = round((float) ($calculation['amount_total'] ?? 0), 2);

        if ($total <= 0) {
            abort(422, 'O valor total do pagamento deve ser maior que zero.');
        }

        DB::transaction(function () use ($order, $calculation, $total, $payer, $components) {
            UserWallet::firstOrCreate(
                ['user_id' => $payer->id],
                ['balance' => 0]
            );

            $wallet = UserWallet::query()
                ->where('user_id', $payer->id)
                ->lockForUpdate()
                ->firstOrFail();

            $balance = (float) $wallet->balance;

            if ($balance + 0.005 < $total) {
                abort(422, 'Saldo insuficiente na carteira para este pagamento.');
            }

            $newBalance = round($balance - $total, 2);
            $wallet->update(['balance' => $newBalance]);

            WalletTransaction::create([
                'user_wallet_id' => $wallet->id,
                'amount' => round(-$total, 2),
                'balance_after' => $newBalance,
                'type' => 'order_payment_boleto',
                'description' => sprintf(
                    'Pagamento pedido #%d (boleto via saldo) — %s',
                    $order->id,
                    implode(', ', $calculation['components'] ?? [])
                ),
            ]);

            OrderPaymentLink::create([
                'order_id' => $order->id,
                'components' => $calculation['components'],
                'payment_method' => 'boleto',
                'installments' => null,
                'amount_artes' => $calculation['amount_artes'],
                'amount_produtos' => $calculation['amount_produtos'],
                'amount_frete' => $calculation['amount_frete'],
                'amount_total' => $calculation['amount_total'],
                'external_payment_link_id' => null,
                'external_order_id' => null,
                'payment_url' => null,
                'status' => 'paid',
                'expires_at' => null,
                'paid_at' => now(),
                'provider_payload' => [
                    'local' => [
                        'source' => 'wallet_balance',
                        'payer_user_id' => $payer->id,
                    ],
                ],
            ]);

            $this->orderPaymentState->refreshPaidFlags($order);

            if (in_array('ARTES', $calculation['components'] ?? [], true)) {
                $this->orderPaymentState->syncBudgetsAfterArtesPaid($order->fresh());
            }
        });
    }
}

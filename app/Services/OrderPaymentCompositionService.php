<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPaymentLink;
use App\Models\Setting;

class OrderPaymentCompositionService
{
    public function getOrderComposition(Order $order): array
    {
        $frete = round((float) ($order->selected_carrier_price ?? 0), 2);
        $totalArea = (float) ($order->total_area ?? 0);

        $produtoVistaUnit = (float) Setting::get('tiny_erp_price_payment', 0);
        $produtoPrazoUnit = (float) Setting::get('tiny_erp_price_installment', 0);

        $produtoVista = round($totalArea * $produtoVistaUnit, 2);
        $produtoPrazo = round($totalArea * $produtoPrazoUnit, 2);

        $totalVista = round((float) ($order->total_amount ?? 0), 2);
        $totalPrazo = round((float) ($order->total_amount_installments ?? 0), 2);

        $artesVista = round(max(0, $totalVista - $produtoVista - $frete), 2);
        $artesPrazo = round(max(0, $totalPrazo - $produtoPrazo - $frete), 2);
        $artes = round(($artesVista + $artesPrazo) / 2, 2);

        return [
            'ARTES' => $artes,
            'PRODUTOS_PIX' => $produtoVista,
            'PRODUTOS_CREDIT_CARD' => $produtoPrazo,
            'FRETE' => $frete,
            'TOTAL_PIX' => round($artes + $produtoVista + $frete, 2),
            'TOTAL_CREDIT_CARD' => round($artes + $produtoPrazo + $frete, 2),
        ];
    }

    public function calculateSelectedAmount(Order $order, array $components, string $paymentMethod): array
    {
        $composition = $this->getOrderComposition($order);
        $selected = array_values(array_unique($components));

        $paid = [
            'ARTES' => (float) OrderPaymentLink::query()
                ->where('order_id', $order->id)
                ->where('status', 'paid')
                ->sum('amount_artes'),
            'PRODUTOS' => (float) OrderPaymentLink::query()
                ->where('order_id', $order->id)
                ->where('status', 'paid')
                ->sum('amount_produtos'),
            'FRETE' => (float) OrderPaymentLink::query()
                ->where('order_id', $order->id)
                ->where('status', 'paid')
                ->sum('amount_frete'),
        ];

        $remaining = [
            'ARTES' => round(max(0, (float) $composition['ARTES'] - $paid['ARTES']), 2),
            'PRODUTOS_PIX' => round(max(0, (float) $composition['PRODUTOS_PIX'] - $paid['PRODUTOS']), 2),
            'PRODUTOS_CREDIT_CARD' => round(max(0, (float) $composition['PRODUTOS_CREDIT_CARD'] - $paid['PRODUTOS']), 2),
            'FRETE' => round(max(0, (float) $composition['FRETE'] - $paid['FRETE']), 2),
        ];

        // Geração de link sempre usa somente o saldo pendente (diferença)
        $amountArtes = in_array('ARTES', $selected, true) ? $remaining['ARTES'] : 0.0;
        $amountFrete = in_array('FRETE', $selected, true) ? $remaining['FRETE'] : 0.0;
        $amountProdutos = 0.0;

        if (in_array('PRODUTOS', $selected, true)) {
            $amountProdutos = $paymentMethod === 'pix'
                ? $remaining['PRODUTOS_PIX']
                : $remaining['PRODUTOS_CREDIT_CARD'];
        }

        return [
            'components' => $selected,
            'amount_artes' => round($amountArtes, 2),
            'amount_produtos' => round($amountProdutos, 2),
            'amount_frete' => round($amountFrete, 2),
            'amount_total' => round($amountArtes + $amountProdutos + $amountFrete, 2),
            'composition' => $composition,
            'paid' => $paid,
            'remaining' => $remaining,
        ];
    }
}


<?php

namespace App\Services\Tiny\Payloads;

use App\Services\Tiny\TinyCarrierMapper;
use App\Services\Tiny\TinyCustomerResolver;

class TinyInvoicePayload
{
    public function __construct(
        protected TinyCustomerResolver $customers,
        protected TinyCarrierMapper $carriers,
    ) {}

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>|null  $dropshipping
     * @return array<string, mixed>
     */
    public function make(array $order, ?array $dropshipping = null): array
    {
        $customer = $this->customers->resolve($order, $dropshipping);
        $carrier = $this->carriers->parse((string) ($order['selected_carrier_name'] ?? ''));

        return [
            'nota_fiscal' => [
                'natureza_operacao' => 'Venda de Mercadorias',
                'tipo' => 'S',
                'cliente' => $this->customers->makeClientData($customer),
                'endereco_entrega' => $this->customers->makeAddressData($customer),
                'itens' => [
                    [
                        'item' => [
                            'descricao' => $order['comment_referring_model']
                                ?? 'Orçamento para papel de parede',
                            'valor_unitario' => $order['payment_method'] === 'pix'
                                ? $order['total_amount']
                                : $order['total_amount_installments'],
                            'gtin_ean' => config('app.tiny_erp_settings.tiny_erp_gtin')
                                ?? config('app.tiny_erp_settings.gtin')
                                ?? '',
                            'quantidade' => 1,
                            'unidade' => 'UN',
                            'ncm' => config('app.tiny_erp_settings.tiny_erp_ncm')
                                ?? config('app.tiny_erp_settings.ncm')
                                ?? '4814.20.00',
                            'tipo' => 'P',
                            'origem' => '0',
                        ],
                    ],
                ],
                'transportador' => [
                    'nome' => $carrier['name'],
                ],
                'frete_por_conta' => 'D',
                'quantidade_volumes' => max(1, (int) ($order['quantity_volumes'] ?? 1)),
                'forma_pagamento' => $order['payment_method'] === 'pix'
                    ? 'pix'
                    : 'multiplas',
                'forma_envio' => $this->carriers->shippingCodeByOrigin(
                    (string) ($order['selected_carrier_name'] ?? '')
                ),
                'valor_frete' => $order['selected_carrier_price'] ?? 0,
                'finalidade' => '3',
                'obs' => 'NF emitida pelo sistema Papel de parede',
                'ecommerce' => 'Sistema de Papel de parede',
                'numero_pedido_ecommerce' => $order['id'],
            ],
        ];
    }
}

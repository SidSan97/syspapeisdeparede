<?php

namespace App\Services\Tiny\Payloads;

use App\Services\Tiny\TinyCarrierMapper;
use App\Services\Tiny\TinyCustomerResolver;
use App\Services\Tiny\TinyHttpClient;
use Carbon\Carbon;

class TinyOrderPayload
{
    public function __construct(
        protected TinyCustomerResolver $customers,
        protected TinyCarrierMapper $carriers,
        protected TinyHttpClient $http,
    ) {}

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>|null  $dropshipping
     */
    public function make(array $order, ?array $dropshipping = null): string
    {
        $customer = $this->customers->resolve($order, $dropshipping);

        $dataPedido = Carbon::parse($order['created_at']);

        $dataPrevista = $dataPedido
            ->copy()
            ->addDays((int) ($order['delivery_time'] ?? 0));

        $carrier = $this->carriers->parse((string) ($order['selected_carrier_name'] ?? ''));

        return $this->http->encode([
            'pedido' => [
                'data_pedido' => $dataPedido->format('d/m/Y'),
                'data_prevista' => $dataPrevista->format('d/m/Y'),
                'cliente' => $this->customers->makeClientData($customer),
                'itens' => [
                    [
                        'item' => [
                            'codigo' => $order['id'],
                            'descricao' => $order['comment_referring_model']
                                ?? 'Orçamento para papel de parede',
                            'unidade' => 'UN',
                            'quantidade' => 1,
                            'valor_unitario' => $order['payment_method'] === 'pix'
                                ? $order['total_amount']
                                : $order['total_amount_installments'],
                        ],
                    ],
                ],
                'nome_transportador' => $carrier['name'],
                'forma_pagamento' => $order['payment_method'] === 'pix'
                    ? 'pix'
                    : 'credito',
                'frete_por_conta' => 'D',
                'valor_frete' => $order['selected_carrier_price'] ?? 0,
                'numero_ordem_compra' => '',
                'ecommerce' => 'Papel de parede',
                'situacao' => 'aprovado',
                'obs' => $carrier['service'],
                'forma_envio' => $this->carriers->shippingCodeByOrigin(
                    (string) ($order['selected_carrier_name'] ?? '')
                ),
                'intermediador' => [
                    'nome' => 'Papel de parede',
                    'cnpj' => '13.023.181/0001-13',
                ],
            ],
        ]);
    }
}

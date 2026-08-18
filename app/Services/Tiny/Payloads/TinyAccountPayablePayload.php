<?php

namespace App\Services\Tiny\Payloads;

use App\Services\Tiny\TinyCustomerResolver;
use App\Services\Tiny\TinyHttpClient;
use Carbon\Carbon;

class TinyAccountPayablePayload
{
    public function __construct(
        protected TinyCustomerResolver $customers,
        protected TinyHttpClient $http,
    ) {}

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>|null  $dropshipping
     */
    public function make(array $order, ?array $dropshipping = null): string
    {
        $customer = $this->customers->resolve($order, $dropshipping);
        $currentDate = Carbon::now();

        return $this->http->encode([
            'conta' => [
                'cliente' => $this->customers->makeClientData($customer),
                'data' => $currentDate->format('d/m/Y'),
                'vencimento' => $currentDate->copy()->addDays(3)->format('d/m/Y'),
                'valor' => $order['payment_method'] === 'pix'
                    ? $order['total_amount']
                    : $order['total_amount_installments'],
                'nro_documento' => '',
                'categoria' => '',
                'ocorrencia' => $order['payment_method'] === 'pix' ? 'U' : 'P',
                'numero_parcelas' => $order['payment_method'] === 'pix'
                    ? 0
                    : (int) ($order['installments'] ?? 1),
            ],
        ]);
    }
}

<?php

namespace App\Services\Tiny;

use App\Services\Tiny\Payloads\TinyAccountPayablePayload;

class TinyAccountPayableService
{
    public function __construct(
        protected TinyHttpClient $http,
        protected TinyAccountPayablePayload $payload,
    ) {}

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>|null  $dropshipping
     * @return array<string, mixed>
     */
    public function send(array $order, ?array $dropshipping = null): array
    {
        return $this->http->post('conta.pagar.incluir.php', [
            'conta' => $this->payload->make($order, $dropshipping),
        ]);
    }
}

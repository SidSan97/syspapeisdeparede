<?php

namespace App\Services\Tiny;

use App\Services\Tiny\Payloads\TinyOrderPayload;
use Illuminate\Support\Facades\Log;

class TinyOrderService
{
    public function __construct(
        protected TinyHttpClient $http,
        protected TinyOrderPayload $payload,
    ) {}

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>|null  $dropshipping
     * @return array<string, mixed>
     */
    public function send(array $order, ?array $dropshipping = null): array
    {
        Log::info('Iniciando envio de pedido ao Tiny ERP', [
            'order_id' => $order['id'] ?? null,
            'user_id' => $order['user_id'] ?? null,
        ]);

        $retorno = $this->http->post('pedido.incluir.php', [
            'pedido' => $this->payload->make($order, $dropshipping),
        ]);

        Log::info('Retorno do envio de pedido ao Tiny ERP', [
            'order_id' => $order['id'] ?? null,
            'tiny_status' => $retorno['status'] ?? null,
            'status_processamento' => $retorno['status_processamento'] ?? null,
            'codigo_erro' => $retorno['codigo_erro'] ?? null,
        ]);

        return $retorno;
    }
}

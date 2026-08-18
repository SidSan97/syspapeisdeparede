<?php

namespace App\Services\Tiny;

use App\Services\Tiny\Payloads\TinyInvoicePayload;

class TinyInvoiceService
{
    public function __construct(
        protected TinyHttpClient $http,
        protected TinyInvoicePayload $payload,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function search(?int $orderId = null): array
    {
        $params = [
            'situacao' => 6,
        ];

        if ($orderId) {
            $params['numeroEcommerce'] = $orderId;
        }

        return $this->http->get('notas.fiscais.pesquisa.php', $params);
    }

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>|null  $dropshipping
     * @return array<string, mixed>
     */
    public function send(array $order, ?array $dropshipping = null): array
    {
        return $this->http->post('nota.fiscal.incluir.php', [
            'nota' => $this->payload->make($order, $dropshipping),
        ]);
    }

    /**
     * @param  array<string, mixed>  $invoiceData
     * @return array<string, mixed>
     */
    public function issue(array $invoiceData): array
    {
        return $this->http->get('nota.fiscal.emitir.php', [
            'id' => $invoiceData['nf_id'],
            'numero' => $invoiceData['nf_number'],
            'serie' => $invoiceData['nf_serie'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function generateDanfe(string $id): array
    {
        return $this->http->get('nota.fiscal.obter.link.php', [
            'id' => $id,
        ]);
    }
}

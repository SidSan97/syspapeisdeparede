<?php

namespace App\Services\Tiny;

use App\Services\Tiny\Payloads\TinyExpeditionPayload;
use Illuminate\Support\Facades\Cache;

class TinyExpeditionApiService
{
    public function __construct(
        protected TinyHttpClient $http,
        protected TinyCarrierMapper $carriers,
        protected TinyExpeditionPayload $payload,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function searchGroupings(string $carrier): array
    {
        return $this->http->get('expedicao.pesquisar.agrupamentos.php', [
            'formaEnvio' => $this->carriers->shippingCodeByOrigin($carrier),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function sendInvoiceToExpedition(string $nfIds, string $typeObjects): array
    {
        return $this->http->post('expedicao.liberar.objetos.php', [
            'idObjetos' => $nfIds,
            'tipoObjetos' => $typeObjects,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function includeGroupingInvoices(int|string $invoicesIds): array
    {
        return $this->http->post('expedicao.incluir.agrupamento.php', [
            'idsExpedicao' => $invoicesIds,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function changeExpedition(string $expeditionId, string $carrier): array
    {
        $tinyErpData = Cache::get('tiny_erp_all_data', []);

        return $this->http->post('expedicao.alterar.php', [
            'expedicao' => $this->payload->make(
                $carrier,
                is_array($tinyErpData) ? $tinyErpData : [],
                $expeditionId
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function completeGroupingInvoices(int|string $groupingId): array
    {
        return $this->http->post('expedicao.concluir.agrupamento.php', [
            'idAgrupamento' => $groupingId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function printCarrierLabels(int|string $groupingId): array
    {
        return $this->http->post('expedicao.obter.etiquetas.impressao.php', [
            'idAgrupamento' => $groupingId,
        ]);
    }
}

<?php

namespace App\Services\Tiny\Payloads;

use App\Services\Tiny\TinyCarrierMapper;
use App\Services\Tiny\TinyHttpClient;
use InvalidArgumentException;

class TinyExpeditionPayload
{
    public function __construct(
        protected TinyCarrierMapper $carriers,
        protected TinyHttpClient $http,
    ) {}

    /**
     * @param  array<string, mixed>  $tinyErpData
     */
    public function make(string $carrier, array $tinyErpData, string $expeditionId): string
    {
        $required = [
            'peso_bruto',
            'alturaEmbalagem',
            'larguraEmbalagem',
            'comprimentoEmbalagem',
            'diametroEmbalagem',
        ];

        foreach ($required as $field) {
            if (! array_key_exists($field, $tinyErpData)) {
                throw new InvalidArgumentException(
                    "Configuração '{$field}' não encontrada para a expedição."
                );
            }
        }

        $carrierData = $this->carriers->parse($carrier);

        return $this->http->encode([
            'expedicao' => [
                'id' => $expeditionId,
                'formaEnvio' => $this->carriers->shippingCodeByOrigin($carrier),
                'qtdVolumes' => 1,
                'pesoBruto' => $tinyErpData['peso_bruto'],
                'possuiValorDeclarado' => 'N',
                'valorDeclarado' => 0,
                'possuiAR' => 'N',
                'embalagem' => [
                    'tipo' => '2',
                    'altura' => $tinyErpData['alturaEmbalagem'],
                    'largura' => $tinyErpData['larguraEmbalagem'],
                    'comprimento' => $tinyErpData['comprimentoEmbalagem'],
                    'diametro' => $tinyErpData['diametroEmbalagem'],
                ],
                'transportadora' => [
                    'nome' => $carrierData['name'],
                ],
            ],
        ]);
    }
}

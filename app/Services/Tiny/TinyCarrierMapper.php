<?php

namespace App\Services\Tiny;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TinyCarrierMapper
{
    /**
     * @return array{name: string, service: string}
     */
    public function parse(string $carrier): array
    {
        $parts = array_map(
            'trim',
            explode(' - ', $carrier, 2)
        );

        return [
            'name' => $parts[0] ?? '',
            'service' => $parts[1] ?? '',
        ];
    }

    public function shippingCodeByOrigin(string $origem): ?string
    {
        $origem = trim($origem);

        if (str_contains($origem, ' - ')) {
            $origem = trim(explode(' - ', $origem, 2)[0]);
        }

        $origemToCode = [
            'Correios' => 'C',
            'Transportadora' => 'T',
            'Mercado Envios' => 'M',
            'Correios E-fulfillment' => 'E',
            'B2W Entrega' => 'B',
            'Customizada' => 'X',
            'Conectalá Etiquetas' => 'D',
            'Jadlog' => 'J',
            'Sem Frete' => 'S',
            'Total Express' => 'TOTALEXPRESS',
            'Gateway logistico' => 'GATEWAY',
            'Magalu Entregas' => 'MAGALU_ENTREGAS',
            'Magalu Fulfillment' => 'MAGALU_FULFILLMENT',
            'Shopee Envios' => 'SHOPEE_ENVIOS',
            'Netshoes Entregas' => 'NS_ENTREGAS',
            'Via Varejo Envvias' => 'VIAVAREJO_ENVVIAS',
            'AliExpress Envios' => 'ALI_ENVIOS',
            'Madeira Envios' => 'MADEIRA_ENVIOS',
            'Loggi' => 'LOGGI',
            'Amazon DBA' => 'AMAZON_DBA',
            'Magalu Entregas por Netshoes' => 'NS_MAGALU_ENTREGAS',
            'Olist' => 'OLIST',
        ];

        foreach ($origemToCode as $nomeOrigem => $codigo) {
            if (strcasecmp($origem, $nomeOrigem) === 0) {
                return $codigo;
            }
        }

        Log::warning('Forma de envio não encontrada no mapeamento do Tiny ERP', [
            'origem' => $origem,
        ]);

        return null;
    }

    public function carrierId(string $carrier): ?string
    {
        $carriersTypes = Cache::get('tiny_erp_carriers_types', []);

        if (! is_array($carriersTypes)) {
            return null;
        }

        foreach ($carriersTypes as $carrierType) {
            if (
                isset($carrierType['descricao'], $carrierType['id'])
                && $carrierType['descricao'] === $carrier
            ) {
                return (string) $carrierType['id'];
            }
        }

        return null;
    }
}

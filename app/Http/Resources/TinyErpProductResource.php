<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TinyErpProductResource extends JsonResource
{
    public static function makeTinyErpData(array $data)
    {
        $result = [];

        // De $data['products'] - extrair id e nome de cada produto
        if (isset($data['products']['produtos']) && is_array($data['products']['produtos'])) {
            $result['products'] = [];
            foreach ($data['products']['produtos'] as $item) {
                if (isset($item['produto'])) {
                    $produto = $item['produto'];
                    $result['products'][] = [
                        'id' => isset($produto['id']) ? (int) $produto['id'] : null,
                        'nome' => $produto['nome'] ?? null,
                    ];
                }
            }
        }

        // De $data['product'] - extrair peso_liquido, peso_bruto e dimensões da embalagem
        if (isset($data['product']['produto'])) {
            $product = $data['product']['produto'];
            $result['product'] = [
                'peso_liquido' => isset($product['peso_liquido']) ? (float) $product['peso_liquido'] : null,
                'peso_bruto' => isset($product['peso_bruto']) ? (float) $product['peso_bruto'] : null,
                'alturaEmbalagem' => isset($product['alturaEmbalagem']) ? (float) $product['alturaEmbalagem'] : null,
                'comprimentoEmbalagem' => isset($product['comprimentoEmbalagem']) ? (float) $product['comprimentoEmbalagem'] : null,
                'larguraEmbalagem' => isset($product['larguraEmbalagem']) ? (float) $product['larguraEmbalagem'] : null,
                'diametroEmbalagem' => isset($product['diametroEmbalagem']) ? (float) $product['diametroEmbalagem'] : null,
            ];
        }

        // De $data['pricePaymentExceptions'] - extrair preco_promocional
        $result['pricePaymentExceptions'] = [];
        if (isset($data['pricePaymentExceptions']['registros']) && is_array($data['pricePaymentExceptions']['registros'])) {
            foreach ($data['pricePaymentExceptions']['registros'] as $item) {
                if (isset($item['registro'])) {
                    $registro = $item['registro'];
                    $result['pricePaymentExceptions'][] = [
                        'preco_promocional' => isset($registro['preco_promocional']) ? (float) $registro['preco_promocional'] : null,
                    ];
                }
            }
        }

        // De $data['priceInstallmentExceptions'] - extrair preco_promocional
        $result['priceInstallmentExceptions'] = [];
        if (isset($data['priceInstallmentExceptions']['registros']) && is_array($data['priceInstallmentExceptions']['registros'])) {
            foreach ($data['priceInstallmentExceptions']['registros'] as $item) {
                if (isset($item['registro'])) {
                    $registro = $item['registro'];
                    $result['priceInstallmentExceptions'][] = [
                        'preco_promocional' => isset($registro['preco_promocional']) ? (float) $registro['preco_promocional'] : null,
                    ];
                }
            }
        }

        return [
            'produto_id' => $result['products'][0]['id'],
            'produto_nome' => $result['products'][0]['nome'],
            'peso_liquido' => $result['product']['peso_liquido'],
            'peso_bruto' => $result['product']['peso_bruto'],
            'alturaEmbalagem' => $result['product']['alturaEmbalagem'],
            'comprimentoEmbalagem' => $result['product']['comprimentoEmbalagem'],
            'larguraEmbalagem' => $result['product']['larguraEmbalagem'],
            'diametroEmbalagem' => $result['product']['diametroEmbalagem'],
            'precoPromocionalPrazo' => $result['pricePaymentExceptions'][0]['preco_promocional'],
            'precoPromocionalVista' => $result['priceInstallmentExceptions'][0]['preco_promocional'],
        ];
    }
}


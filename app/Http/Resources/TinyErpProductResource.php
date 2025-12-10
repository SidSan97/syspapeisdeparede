<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TinyErpProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $result = [];

        // De $data['products'] - extrair id e nome de cada produto
        if (isset($this->resource['products']['produtos']) && is_array($this->resource['products']['produtos'])) {
            $result['products'] = [];
            foreach ($this->resource['products']['produtos'] as $item) {
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
        if (isset($this->resource['product']['produto'])) {
            $product = $this->resource['product']['produto'];
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
        if (isset($this->resource['pricePaymentExceptions']['registros']) && is_array($this->resource['pricePaymentExceptions']['registros'])) {
            foreach ($this->resource['pricePaymentExceptions']['registros'] as $item) {
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
        if (isset($this->resource['priceInstallmentExceptions']['registros']) && is_array($this->resource['priceInstallmentExceptions']['registros'])) {
            foreach ($this->resource['priceInstallmentExceptions']['registros'] as $item) {
                if (isset($item['registro'])) {
                    $registro = $item['registro'];
                    $result['priceInstallmentExceptions'][] = [
                        'preco_promocional' => isset($registro['preco_promocional']) ? (float) $registro['preco_promocional'] : null,
                    ];
                }
            }
        }

        dd($result);
        return $result;
    }
}


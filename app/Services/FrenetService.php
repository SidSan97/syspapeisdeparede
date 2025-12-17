<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;

class FrenetService
{
    public function shippingData(array $item)
    {
        $apiKey = env('FRENET_TOKEN');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Chave da API Frenet não configurada.',
            ], 500);
        }

        $client = new Client([
            'base_uri' => 'http://api.frenet.com.br',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'token' => $apiKey,
            ],
        ]);

        $body = [
            "SellerCEP" => config('app.tiny_erp_settings.cep'),
            "RecipientCEP" => $item['cep'],
            "RecipientCountry" => "BR",
            "ShippingItemArray" => [
              [
                "Weight" => $item['productData']['peso_bruto'],
                "Length" => $item['productData']['comprimentoEmbalagem'],
                "Height" => $item['productData']['alturaEmbalagem'],
                "Quantity" => 1,
                "isFragile" => false,
                "Width" => $item['productData']['larguraEmbalagem']
              ]
            ],
        ];

        try {
            $response = $client->post('/shipping/quote', [
                'json' => $body,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Frete calculado com sucesso.',
                'data' => json_decode($response->getBody(), true),
            ], 200);
        }catch (GuzzleException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao calcular frete.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

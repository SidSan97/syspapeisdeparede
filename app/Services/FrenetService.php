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
        $apiKey = env('FRENET_API_KEY');

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

        try {
            $response = $client->post('/shipping/quote', [
                'json' => $item,
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

<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GeneratePaymentService
{
    /**
     * Dispara a criação de um link de pagamento no Pagar.me
     */
    public function generateLinkPayment(array $budget): JsonResponse
    {
        $apiKey = env('PAGARME_API_KEY');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Chave da API Pagar.me não configurada.',
            ], 500);
        }

        $client = new Client([
            'base_uri' => 'https://sdx-api.pagar.me',
            'timeout' => 10,
        ]);

        try {
            $response = $client->post('/core/v5/paymentlinks', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode($apiKey . ':'),
                ],
                'json' => ($budget['payment_method'] === 'pix')
                    ? $this->makePixPayloadData($budget)
                    : $this->makeCreditCardPayloadData($budget),
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return response()->json([
                'success' => true,
                'data' => $body,
            ], $response->getStatusCode());
        } catch (GuzzleException $e) {
            Log::error('Erro ao gerar link de pagamento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar link de pagamento.',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Erro ao gerar link de pagamento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar link de pagamento.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /*
    * Gera o payload para o PIX
    * @param array $budget
    * @return array
    */
    protected function makePixPayloadData(array $budget): array
    {
        return [
            "is_building" => false,
            "payment_settings" => [
                "accepted_payment_methods" => [
                   'pix'
                ],
                "pix_settings" => [
                    "additional_information" => [
                        "Name" => "Papel de parede",
                        "Value" => "Papel de parede"
                    ],
                    "expires_in" => 86400 //24h em segundos
                ]
            ],
            "cart_settings" => [
                "items" => [
                    [
                        "name" => "Papel de parede / " . $budget['name'],
                        "amount" => intval($budget['total_amount'] * 100), //valor do item em centavos
                        "default_quantity" => 1,
                        "description" => $budget['comments'] ?? ''
                    ]
                ],
                "shipping_cost" => intval(($budget['carrier_price'] ?? 0) * 100) //valor do frete em centavos
            ],
            "type" => "order"
        ];
    }

    /*
    * Gera o payload para o cartão de crédito
    * @param array $budget
    * @return array
    */
    protected function makeCreditCardPayloadData(array $budget): array
    {
        return [
            "is_building" => false,
            "payment_settings" => [
                "accepted_payment_methods" => [
                    "credit_card"
                ],
                "credit_card_settings" => [
                    "installments" => [
                        [
                            "number" => $budget['installments'] ?? 1,
                            "total" => intval(($budget['total_amount_installments'] ?? 0) * 100) //valor do item em centavos
                        ]
                    ],
                    "operation_type" => "auth_and_capture",
                    "delay_to_capture" => 2
                ]
            ],
            "cart_settings" => [
                "items" => [
                    [
                        "name" => "Papel de parede / " . $budget['name'],
                        "amount" => intval(($budget['total_amount_installments'] ?? 0) * 100), //valor do item em centavos
                        "description" => $budget['comments'] ?? '',
                        "default_quantity" => 1
                    ]
                ],
                "shipping_cost" => intval(($budget['carrier_price'] ?? 0) * 100) //valor do frete em centavos
            ],
            "name" => "Papel de parede",
            "type" => "order",
            "expires_in" => 1440 //24h em minutos
        ];
    }
}

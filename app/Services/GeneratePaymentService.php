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
        $apiKey = config('services.pagarme.api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Chave da API Pagar.me não configurada.',
            ], 500);
        }

        $client = new Client([
            'base_uri' => config('services.pagarme.base_url'),
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
                        "description" => $this->buildItemDescription($budget)
                    ]
                ],
                "shipping_cost" => intval(($budget['carrier_price'] ?? 0) * 100) //valor do frete em centavos
            ],
            "metadata" => $this->buildMetadata($budget),
            "type" => "order",
            "expires_at" => now()->addHours(24)->toIso8601String() //24h a partir de agora
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
                        "description" => $this->buildItemDescription($budget),
                        "default_quantity" => 1
                    ]
                ],
                "shipping_cost" => intval(($budget['carrier_price'] ?? 0) * 100) //valor do frete em centavos
            ],
            "metadata" => $this->buildMetadata($budget),
            "name" => "Papel de parede",
            "type" => "order",
            "expires_at" => now()->addHours(24)->toIso8601String() //24h a partir de agora
        ];
    }

    protected function buildItemDescription(array $budget): string
    {
        $comments = trim((string) ($budget['comments'] ?? ''));
        $ref = isset($budget['id']) ? " [order_ref:{$budget['id']}]" : '';

        return $comments !== '' ? $comments . $ref : ltrim($ref);
    }

    /**
     * Metadata para vincular o pedido Pagar.me ao nosso Order (usado no webhook).
     */
    protected function buildMetadata(array $budget): array
    {
        if (! isset($budget['id'])) {
            return [];
        }

        return ['order_id' => (string) $budget['id']];
    }
}

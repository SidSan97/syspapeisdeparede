<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;

class GeneratePaymentService
{
    public function generateLinkPayment(): JsonResponse
    {
        return response()->json([
            'payment_settings' => [
                'cart_settings' => [
                    'items' => [
                        [
                            'amount' => 12000,
                            'name' => 'Banner',
                            'default_quantity' => 1,
                        ],
                    ],
                    'items_total_cost' => 12000,
                    'total_cost' => 12000,
                    'shipping_cost' => 0,
                    'shipping_total_cost' => 0,
                ],
            ],
            'name' => 'Banner N12345',
            'type' => 'order',
            'total_sessions' => 0,
            'max_paid_sessions' => 0,
            'total_paid_sessions' => 0,
            'max_sessions' => 0,
            'created_at' => '2024-05-13T01:09:40.6331583Z',
            'url' => 'https://payment-link.pagar.me/pl_GNe8zkaO2MlBxxGcJcv0BALq9Pon514W',
            'updated_at' => '2024-05-13T01:09:40.6331583Z',
            'id' => 'pl_GNe8zkaO2MlBxxGcJcv0BALq9Pon514W',
            'expires_in' => 0,
            'status' => 'active',
        ]);
    }
}

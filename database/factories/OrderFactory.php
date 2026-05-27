<?php

namespace Database\Factories;

use App\Models\CollectionModel;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = [
            ['status' => 'em aberto', 'flags' => null],
            ['status' => 'aprovado', 'flags' => 'Pagamento recebido'],
            ['status' => 'Em produção', 'flags' => 'Produção em andamento'],
            ['status' => 'Enviado', 'flags' => 'Produção concluída'],
            ['status' => 'cancelado', 'flags' => null],
        ];
        $statusFlags = fake()->randomElement($statuses);
        $paymentMethods = ['dinheiro', 'cartão de crédito', 'cartão de débito', 'pix', 'boleto'];
        $carriers = ['Jadlog', 'Transportadora XYZ', 'Logística ABC', 'Express Delivery', 'Correios',
            'Total Express', 'Gateway logistico', 'Magalu Entregas', 'Magalu Fulfillment',
            'Shopee Envios', 'Netshoes Entregas', 'Via Varejo Envvias', 'AliExpress Envios',
            'Madeira Envios', 'Loggi', 'Amazon DBA', 'Magalu Entregas por Netshoes', 'Olist'];

        $carrier = $carriers[array_rand($carriers)];

        $totalAmount = fake()->randomFloat(2, 500, 50000);
        $totalAmountInstallments = fake()->boolean(70) ? round($totalAmount * 1.1, 2) : 0;
        $markupFactor = fake()->boolean(60) ? round(fake()->randomFloat(2, 1.02, 1.15), 2) : null;

        $isPaid = fake()->boolean(30);
        $hasPaymentLink = fake()->boolean(40) && ! $isPaid;

        return [
            'user_id' => User::factory(),
            'tenant_id' => function (array $attributes) {
                // tenant_id geralmente é o mesmo que user_id
                return $attributes['user_id'] ?? User::factory();
            },
            'name' => fake()->sentence(3, false),
            'total_area' => fake()->randomFloat(2, 10, 500),
            'total_amount' => $totalAmount,
            'total_amount_installments' => $totalAmountInstallments,
            'total_amount_markup' => $markupFactor !== null ? round($totalAmount * $markupFactor, 2) : null,
            'total_amount_installments_markup' => $markupFactor !== null && $totalAmountInstallments > 0
                ? round($totalAmountInstallments * $markupFactor, 2)
                : null,
            'delivery_time' => fake()->numberBetween(7, 45),
            'payment_method' => fake()->optional()->randomElement(['pix', 'installment']),
            'installments' => fake()->numberBetween(1, 6),
            'cep' => fake()->postcode(),
            'selected_carrier_name' => $carrier.' - Package',
            'selected_carrier_price' => fake()->randomFloat(2, 50, 500),
            'selected_carrier_delivery_time' => fake()->numberBetween(5, 15),
            'carriers_snapshot' => [
                $carrier => [
                    'name' => $carrier,
                    'price' => fake()->randomFloat(2, 50, 500),
                    'delivery_time' => fake()->numberBetween(5, 15),
                ],
            ],
            'status' => $statusFlags['status'],
            'flags' => $statusFlags['flags'],
            'dropshipping_budget' => fake()->boolean(50) ? 1 : 0,
            'link_payment' => $hasPaymentLink ? fake()->url() : null,
            'payment_expiration_date' => $hasPaymentLink ? fake()->dateTimeBetween('now', '+7 days')->format('Y-m-d H:i:s') : null,
            'paid' => $isPaid ? 1 : 0,
            'nf_sent' => fake()->boolean(20) ? 1 : 0,
            'nf_id' => fake()->boolean(15) ? fake()->uuid() : null,
        ];
    }

    /**
     * Indicate that the order has dropshipping data.
     */
    public function withDropshipping(): static
    {
        return $this->afterCreating(function (Order $order) {
            \App\Models\DropshippingData::factory()->create([
                'order_id' => $order->id,
                'dealer_id' => $order->user_id,
            ]);
        });
    }

    /**
     * Indicate that the order has rooms and walls.
     */
    public function withRooms(): static
    {
        return $this->afterCreating(function (Order $order) {
            $roomCount = fake()->numberBetween(1, 5);
            $collectionModelIds = CollectionModel::pluck('id')->toArray();

            for ($i = 0; $i < $roomCount; $i++) {
                $room = \App\Models\BudgetRoom::factory()->create([
                    'order_id' => $order->id,
                    'tenant_id' => $order->tenant_id,
                    'position' => $i + 1,
                ]);

                $wallCount = fake()->numberBetween(1, 4);
                for ($j = 0; $j < $wallCount; $j++) {
                    $width = fake()->randomFloat(2, 2, 10);
                    $height = fake()->randomFloat(2, 2.5, 3.5);
                    $totalArea = $width * $height;

                    \App\Models\BudgetWall::factory()->create([
                        'budget_room_id' => $room->id,
                        'tenant_id' => $order->tenant_id,
                        'position' => $j + 1,
                        'width' => $width,
                        'height' => $height,
                        'total_area' => $totalArea,
                        'collection_model_id' => ! empty($collectionModelIds)
                            ? fake()->randomElement($collectionModelIds)
                            : CollectionModel::factory(),
                    ]);
                }
            }

            // Atualizar primary_budget_room_id com o primeiro room
            $firstRoom = $order->rooms()->first();
            if ($firstRoom) {
                $order->update(['primary_budget_room_id' => $firstRoom->id]);
            }

            // Recalcular total_area baseado nas paredes
            $totalArea = $order->rooms()
                ->with('walls')
                ->get()
                ->flatMap->walls
                ->sum('total_area');

            $order->update(['total_area' => round($totalArea, 2)]);
        });
    }
}

<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\User;
use App\Models\CollectionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['em aberto', 'aprovado', 'cancelado'];
        $paymentMethods = ['dinheiro', 'cartão de crédito', 'cartão de débito', 'pix', 'boleto'];
        $carriers = ['Jadlog', 'Transportadora XYZ', 'Logística ABC', 'Express Delivery', 'Correios',
        'Total Express', 'Gateway logistico', 'Magalu Entregas', 'Magalu Fulfillment',
        'Shopee Envios', 'Netshoes Entregas', 'Via Varejo Envvias', 'AliExpress Envios',
        'Madeira Envios', 'Loggi', 'Amazon DBA', 'Magalu Entregas por Netshoes', 'Olist'];

        $carrier = $carriers[array_rand($carriers)];

        $totalAmount = fake()->randomFloat(2, 500, 50000);
        $totalAmountInstallments = fake()->boolean(70) ? round($totalAmount * 1.1, 2) : 0;
        $markupFactor = fake()->boolean(60) ? round(fake()->randomFloat(2, 1.02, 1.15), 2) : null;

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
            'payment_method' => fake()->randomElement($paymentMethods),
            'installment_limit' => fake()->numberBetween(1, 12),
            'installments' => fake()->numberBetween(1, 6),
            'cep' => fake()->postcode(),
            'selected_carrier_name' => $carrier . ' - Package',
            'selected_carrier_price' => fake()->randomFloat(2, 50, 500),
            'selected_carrier_delivery_time' => fake()->numberBetween(5, 15),
            'carriers_snapshot' => [
                $carrier => [
                    'name' => $carrier,
                    'price' => fake()->randomFloat(2, 50, 500),
                    'delivery_time' => fake()->numberBetween(5, 15),
                ],
            ],
            'status' => fake()->randomElement($statuses),
            'dropshipping_budget' => fake()->boolean(50) ? 1 : 0,
        ];
    }

    /**
     * Indicate that the budget has dropshipping data.
     */
    public function withDropshipping(): static
    {
        return $this->afterCreating(function (Budget $budget) {
            \App\Models\DropshippingData::factory()->create([
                'budget_id' => $budget->id,
                'dealer_id' => $budget->user_id,
            ]);
        });
    }

    /**
     * Indicate that the budget has rooms and walls.
     */
    public function withRooms(): static
    {
        return $this->afterCreating(function (Budget $budget) {
            $roomCount = fake()->numberBetween(1, 5);
            $collectionModelIds = CollectionModel::pluck('id')->toArray();

            for ($i = 0; $i < $roomCount; $i++) {
                $room = \App\Models\BudgetRoom::factory()->create([
                    'budget_id' => $budget->id,
                    'tenant_id' => $budget->tenant_id,
                    'position' => $i + 1,
                ]);

                $wallCount = fake()->numberBetween(1, 4);
                for ($j = 0; $j < $wallCount; $j++) {
                    $width = fake()->randomFloat(2, 2, 10);
                    $height = fake()->randomFloat(2, 2.5, 3.5);
                    $totalArea = $width * $height;

                    \App\Models\BudgetWall::factory()->create([
                        'budget_room_id' => $room->id,
                        'tenant_id' => $budget->tenant_id,
                        'position' => $j + 1,
                        'width' => $width,
                        'height' => $height,
                        'total_area' => $totalArea,
                        'collection_model_id' => !empty($collectionModelIds)
                            ? fake()->randomElement($collectionModelIds)
                            : CollectionModel::factory(),
                    ]);
                }
            }

            // Atualizar primary_budget_room_id com o primeiro room
            $firstRoom = $budget->rooms()->first();
            if ($firstRoom) {
                $budget->update(['primary_budget_room_id' => $firstRoom->id]);
            }

            // Recalcular total_area e total_amount baseado nas paredes
            $totalArea = $budget->rooms()
                ->with('walls')
                ->get()
                ->flatMap->walls
                ->sum('total_area');

            $budget->update(['total_area' => round($totalArea, 2)]);
        });
    }
}

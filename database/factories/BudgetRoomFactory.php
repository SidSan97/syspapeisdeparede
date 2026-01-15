<?php

namespace Database\Factories;

use App\Models\BudgetRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BudgetRoom>
 */
class BudgetRoomFactory extends Factory
{
    protected $model = BudgetRoom::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roomNames = [
            'Sala de Estar',
            'Quarto Principal',
            'Quarto de Hóspedes',
            'Cozinha',
            'Banheiro',
            'Área de Serviço',
            'Escritório',
            'Varanda',
            'Garagem',
            'Hall de Entrada',
        ];

        return [
            'tenant_id' => \App\Models\User::factory(),
            'name' => fake()->randomElement($roomNames),
            'position' => 1,
            'raw_payload' => null,
        ];
    }
}

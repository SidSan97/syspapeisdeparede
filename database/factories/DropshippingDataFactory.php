<?php

namespace Database\Factories;

use App\Models\DropshippingData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DropshippingData>
 */
class DropshippingDataFactory extends Factory
{
    protected $model = DropshippingData::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $personType = fake()->randomElement(['PF', 'PJ']);
        $isPJ = $personType === 'PJ';

        return [
            'name' => $isPJ ? fake()->company() : fake()->name(),
            'person_type' => $personType,
            'cpf_cnpj' => $isPJ
                ? fake()->cnpj(false)
                : fake()->cpf(false),
            'IE' => $isPJ && fake()->boolean(60) ? fake()->numerify('###.###.###.###') : null,
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'cep' => fake()->postcode(),
            'uf' => fake()->stateAbbr(),
            'state' => fake()->state(),
            'city' => fake()->city(),
            'neighborhood' => fake()->word(),
            'public_space' => fake()->streetName(),
            'number' => fake()->buildingNumber(),
            'complement' => fake()->boolean(40) ? fake()->secondaryAddress() : null,
            'dealer_id' => \App\Models\User::factory(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Reseller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reseller>
 */
class ResellerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tiny_id' => fake()->unique()->numberBetween(100000, 999999),
            'tiny_code' => fake()->boolean(70) ? fake()->bothify('REV-####') : null,
            'name' => fake()->company(),
            'fantasy_name' => fake()->boolean(70) ? fake()->company() : null,
            'cnpj' => fake()->cnpj(false),
            'site' => fake()->boolean(50) ? fake()->url() : null,
            'ie' => fake()->boolean(60) ? fake()->numerify('###.###.###.###') : null,
            'person_type' => 'J',
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'cep' => fake()->postcode(),
            'uf' => fake()->stateAbbr(),
            'city' => fake()->city(),
            'neighborhood' => fake()->word(),
            'public_space' => fake()->streetName(),
            'number' => fake()->buildingNumber(),
            'complement' => fake()->boolean(40) ? fake()->secondaryAddress() : null,
            'status' => 'Ativo',
            'last_sync_at' => now(),
        ];
    }

    /**
     * Indicate that the reseller has been excluded/deactivated in Tiny.
     */
    public function excluded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Excluido',
        ]);
    }

    /**
     * Indicate that the reseller has no e-mail registered in Tiny.
     */
    public function withoutEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => null,
        ]);
    }
}

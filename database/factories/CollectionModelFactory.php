<?php

namespace Database\Factories;

use App\Models\CollectionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CollectionModel>
 */
class CollectionModelFactory extends Factory
{
    protected $model = CollectionModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $modelNames = [
            'Classic',
            'Modern',
            'Vintage',
            'Premium',
            'Standard',
            'Deluxe',
            'Executive',
            'Luxury',
            'Basic',
            'Professional',
        ];

        return [
            'name' => fake()->randomElement($modelNames) . ' ' . fake()->word(),
            'value' => fake()->randomFloat(2, 50, 500),
            'deadline' => fake()->numberBetween(5, 30),
            'request_link' => fake()->boolean(40),
            'request_comment' => fake()->boolean(60),
            'request_file' => fake()->boolean(50),
            'request_collection' => fake()->boolean(30),
        ];
    }
}

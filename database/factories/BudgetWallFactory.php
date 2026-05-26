<?php

namespace Database\Factories;

use App\Models\BudgetWall;
use App\Models\CollectionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BudgetWall>
 */
class BudgetWallFactory extends Factory
{
    protected $model = BudgetWall::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $width = fake()->randomFloat(2, 2, 10);
        $height = fake()->randomFloat(2, 2.5, 3.5);
        $totalArea = $width * $height;
        $stripHeight = fake()->randomFloat(2, 0.53, 0.70);
        $stripCount = (int) ceil($height / $stripHeight);

        return [
            'tenant_id' => \App\Models\User::factory(),
            'name' => fake()->randomElement(['Parede Principal', 'Parede Lateral', 'Parede de Fundo', 'Parede']),
            'direction' => fake()->randomElement(['left-to-right', 'right-to-left']),
            'position' => 1,
            'width' => $width,
            'height' => $height,
            'continue_same_art' => fake()->boolean(30),
            'continuations' => [],
            'collection_model_id' => CollectionModel::factory(),
            'total_area' => round($totalArea, 2),
            'strip_height' => round($stripHeight, 2),
            'strip_count' => $stripCount,
            'comment_referring_model' => fake()->boolean(40) ? fake()->sentence() : null,
            'link_referring_model' => fake()->boolean(30) ? fake()->url() : null,
            'files_referring_model' => fake()->boolean(20) ? [fake()->url()] : null,
            'collection_referring_model' => fake()->boolean(30) ? fake()->word() : null,
            'request_layout_referring_model' => fake()->boolean(30),
        ];
    }
}

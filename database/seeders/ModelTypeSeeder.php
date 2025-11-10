<?php

namespace Database\Seeders;

use App\Models\ModelType;
use Illuminate\Database\Seeder;

class ModelTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Personalizado com Desenhista',
            'Personalizado com Arte Pronta',
            'Arte do Shutterstock',
            'Coleção',
        ];

        foreach ($types as $name) {
            ModelType::firstOrCreate(['name' => $name]);
        }
    }
}


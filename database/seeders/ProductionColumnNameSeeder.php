<?php

namespace Database\Seeders;

use App\Models\ProductionColumnName;
use Illuminate\Database\Seeder;

class ProductionColumnNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $columns = [
            'A fazer',
        ];

        foreach ($columns as $column) {
            ProductionColumnName::firstOrCreate(
                ['name' => $column],
                ['name' => $column]
            );
        }
    }
}


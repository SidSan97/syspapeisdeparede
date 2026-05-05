<?php

namespace Database\Seeders;

use App\Models\LayoutColumnName;
use Illuminate\Database\Seeder;

class LayoutColumnNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $columns = [
            'Desenhista',
            'Versao 01',
            'Revisão 01',
            'Revisão 02',
            'Final',
        ];

        foreach ($columns as $column) {
            LayoutColumnName::firstOrCreate(
                ['name' => $column],
                ['name' => $column]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\CollectionCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CollectionCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'Adulto',
            'Infantil',
            'Lançamento',
        ];

        foreach ($names as $name) {
            CollectionCategory::firstOrCreate(['name' => $name]);
        }
    }
}

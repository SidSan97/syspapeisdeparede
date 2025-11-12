<?php

namespace Database\Seeders;

use App\Models\CollectionArt;
use Illuminate\Database\Seeder;

class CollectionArtSeeder extends Seeder
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
            CollectionArt::firstOrCreate(['name' => $name]);
        }
    }
}


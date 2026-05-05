<?php

namespace Database\Seeders;

use App\Models\CollectionModel;
use Illuminate\Database\Seeder;

class CollectionModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $models = [
            [
                'name' => 'Personalizado com Desenhista',
                'value' => 180.0,
                'deadline' => 8,
                'request_comment' => true,
                'request_link' => false,
                'request_file' => false,
                'request_collection' => false,
            ],
            [
                'name' => 'Personalizado com Arte Pronta',
                'value' => 0.00,
                'deadline' => 1,
                'request_comment' => false,
                'request_link' => false,
                'request_file' => true,
                'request_collection' => false,
            ],
            [
                'name' => 'Arte do Shutterstock',
                'value' => 90.0,
                'deadline' => 1,
                'request_comment' => false,
                'request_link' => true,
                'request_file' => false,
                'request_collection' => false,
            ],
            [
                'name' => 'Coleção Arts',
                'value' => 0.0,
                'deadline' => 1,
                'request_comment' => false,
                'request_link' => false,
                'request_file' => false,
                'request_collection' => true,
            ],
        ];

        foreach ($models as $model) {
            CollectionModel::firstOrCreate(
                ['name' => $model['name']],
                $model
            );
        }
    }
}

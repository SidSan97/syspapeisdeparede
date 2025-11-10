<?php

namespace Database\Seeders;

use App\Models\TypeUser;
use Illuminate\Database\Seeder;

class TypeUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Usuário',
            'Administrador',
            'Revendedor',
            'Designer',
            'Produção',
            'Comercial',
            'Expedição',
            'Representantes',
            'Arquitetos',
        ];

        foreach ($types as $type) {
            TypeUser::firstOrCreate(
                ['name' => $type],
                ['name' => $type]
            );
        }
    }
}

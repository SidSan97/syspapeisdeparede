<?php

namespace Database\Seeders;

use App\Models\CollectionCategory;
use Illuminate\Database\Seeder;

class CollectionCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Adulto' => [
                '3D',
                'Abstrato',
                'Animais',
                'Cimento Queimado',
                'Cozinha',
                'Degradê',
                'Floral',
                'Folhagem',
                'Geométrico',
                'Granilite',
                'Listrado',
                'Madeira',
                'Mapa',
                'Medalhão',
                'Mármore',
                'Painel',
                'Paisagem',
                'Pedras',
                'Ripado',
                'Textura',
                'Tijolinho',
                'Xadrez',
            ],
            'Infantil' => [
                'Abstrato',
                'Adolescente',
                'Arco Íris',
                'Bailarina',
                'Balões e Aviões',
                'Bichinhos',
                'Boiserie',
                'Carrinhos',
                'Corações',
                'Céu e Estrelas',
                'Dinossauros',
                'Espaço Galáxia',
                'Esportes',
                'Fazendinha',
                'Floral',
                'Folhagem',
                'Fundo do Mar',
                'Gamer',
                'Geométrico',
                'Listrado',
                'Mapa Mundi',
                'Marvel',
                'Painel',
                'Poa',
                'Quadriculado',
                'Safari',
                'Unicórnios',
                'Xadrez',
            ],
        ];

        $this->createCategories($categories);
    }

    private function createCategories(array $categories, ?int $parentId = null): void
    {
        foreach ($categories as $name => $children) {

            if (is_int($name)) {
                $name = $children;
                $children = [];
            }

            $category = CollectionCategory::firstOrCreate([
                'name' => $name,
                'parent_id' => $parentId,
            ]);

            if (!empty($children)) {
                $this->createCategories($children, $category->id);
            }
        }
    }
}

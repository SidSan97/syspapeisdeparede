<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            TypeUserSeeder::class,
            DefaultUserSeeder::class,
            CollectionCategoriesSeeder::class,
            CollectionModelSeeder::class,
            LayoutColumnNameSeeder::class,
            ProductionColumnNameSeeder::class,
            BudgetAndOrderSeeder::class,
        ]);
    }
}

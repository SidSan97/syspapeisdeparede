<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'users.create',
            'users.edit',
            'users.view',
            'users.delete',

            'permissions.create',
            'permissions.edit',
            'permissions.view',
            'permissions.delete',

            'budgets.create',
            'budgets.edit',
            'budgets.view',
            'budgets.delete',

            'collection.create',
            'collection.edit',
            'collection.view',
            'collection.delete',

            'orders.create',
            'orders.edit',
            'orders.view',
            'orders.delete',

            'layouts.create',
            'layouts.edit',
            'layouts.view',
            'layouts.delete',

            'production.create',
            'production.edit',
            'production.view',
            'production.delete',

            'expedition.create',
            'expedition.edit',
            'expedition.view',
            'expedition.delete',

            'settings.edit',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}

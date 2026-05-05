<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin',
            'reseller',
            'designer',
            'production',
            'commercial',
            'expedition',
            'representatives',
            'architects',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super admin']);

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo([
                'users.create',
                'users.edit',
                'users.view',
                'users.delete',
            ]);
        }
    }
}

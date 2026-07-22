<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (UserRole::cases() as $role) {
            Role::firstOrCreate([
                'name' => $role->value,
            ]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super admin']);

        $admin = Role::where('name', UserRole::Admin->value)->first();
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

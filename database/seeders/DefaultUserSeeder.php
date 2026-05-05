<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => 'Usuário Admin',
                'email' => 'admin@example.com',
                'user_type_id' => UserType::ADMIN,
            ],
            [
                'name' => 'Usuário Revendedor',
                'email' => 'reseller@example.com',
                'is_dropshipping' => 1,
                'user_type_id' => UserType::RESELLER,
            ],
            [
                'name' => 'Usuário Designer',
                'email' => 'designer@example.com',
                'user_type_id' => UserType::DESIGNER,
            ],
            [
                'name' => 'Usuário Produção',
                'email' => 'production@example.com',
                'user_type_id' => UserType::PRODUCTION,
            ],
            [
                'name' => 'Usuário Comercial',
                'email' => 'commercial@example.com',
                'user_type_id' => UserType::COMMERCIAL,
            ],
            [
                'name' => 'Usuário Expedição',
                'email' => 'expedition@example.com',
                'user_type_id' => UserType::EXPEDITION,
            ],
            [
                'name' => 'Usuário Representante',
                'email' => 'representatives@example.com',
                'user_type_id' => UserType::REPRESENTATIVES,
            ],
            [
                'name' => 'Usuário Arquiteto',
                'email' => 'architects@example.com',
                'user_type_id' => UserType::ARCHITECTS,
            ],
        ];

        foreach ($users as $userData) {
            DB::table('users')->where('email', $userData['email'])->delete();

            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'user_type_id' => $userData['user_type_id'],
            ]);

            $user->markEmailAsVerified();

            // Define role com base no ROLE_MAP
            $roleName = UserType::ROLE_MAP[$userData['user_type_id']];

            $role = Role::firstOrCreate(['name' => $roleName]);

            $user->assignRole($role);
        }
    }
}

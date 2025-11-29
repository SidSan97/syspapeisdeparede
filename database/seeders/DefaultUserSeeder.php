<?php


namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Support\UserType;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->where('email', 'admin@gmail.com')->delete();

        $superAdmin = User::create([
            'name' => 'Usuário Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'user_type_id' => UserType::ADMIN,
        ]);
        $superAdmin->markEmailAsVerified();

        $superAdminRole = Role::firstOrCreate(['name' => 'super admin']);
        $superAdmin->assignRole($superAdminRole);
    }
}

<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->count(25)->reseller()->create();

        foreach (UserRole::internal() as $role) {
            User::factory()
                ->count(10)
                ->role($role)
                ->create();
        }
    }
}

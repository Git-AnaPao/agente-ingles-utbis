<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['role_name' => 'admin'],
            ['role_description' => 'Administrador del sistema']
        );

        $adminUser = User::firstOrCreate(
            ['user_email' => 'admin@utbispuebla.edu.mx'],
            [
                'user_name' => 'Admin',
                'user_last_name' => 'Sistema',
                'user_middle_name' => '',
                'user_cel' => '0000000000',
                'user_password' => Hash::make('Admin123!'),
                'user_status' => 'active',
            ]
        );

        $adminUser->roles()->syncWithoutDetaching([$adminRole->role_id]);
    }
}
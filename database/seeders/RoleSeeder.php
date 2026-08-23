<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['role_name' => 'admin', 'role_description' => 'Administrador del sistema'],
            ['role_name' => 'professor', 'role_description' => 'Profesor / instructor'],
            ['role_name' => 'student', 'role_description' => 'Estudiante'],
        ];

        foreach ($roles as $data) {
            Role::firstOrCreate(
                ['role_name' => $data['role_name']],
                ['role_description' => $data['role_description']],
            );
        }
    }
}

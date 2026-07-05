<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            LessonSeeder::class,
        ]);

        $adminRole = Role::where('role_name', 'admin')->first();
        $profRole = Role::where('role_name', 'professor')->first();
        $studentRole = Role::where('role_name', 'student')->first();

        $admin = User::create([
            'user_email' => 'admin@utbis.edu',
            'user_cel' => '70000000',
            'user_password' => Hash::make('admin123'),
            'user_name' => 'Admin',
            'user_last_name' => 'Sistema',
            'user_middle_name' => '',
            'user_status' => 'active',
        ]);
        $admin->roles()->attach($adminRole->role_id);

        $prof = User::create([
            'user_email' => 'profesor@utbis.edu',
            'user_cel' => '70000001',
            'user_password' => Hash::make('profesor123'),
            'user_name' => 'Profesor',
            'user_last_name' => 'UTBIS',
            'user_middle_name' => '',
            'user_status' => 'active',
        ]);
        $prof->roles()->attach($profRole->role_id);

        $student = User::create([
            'user_email' => 'test@example.com',
            'user_cel' => '70000002',
            'user_password' => Hash::make('password'),
            'user_name' => 'Test',
            'user_last_name' => 'User',
            'user_middle_name' => '',
            'user_status' => 'active',
        ]);
        $student->roles()->attach($studentRole->role_id);
    }
}

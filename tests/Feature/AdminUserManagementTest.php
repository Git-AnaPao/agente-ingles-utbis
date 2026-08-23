<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_user_with_all_profile_fields(): void
    {
        $adminRole = $this->role('admin');
        $studentRole = $this->role('student');
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'user_name' => 'Ana María',
            'user_last_name' => 'López',
            'user_middle_name' => 'Pérez',
            'user_cel' => '7123456789',
            'email' => 'ana@utbispuebla.edu.mx',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'role' => 'student',
            'user_status' => 'inactive',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users'));

        $created = User::where('user_email', 'ana@utbispuebla.edu.mx')->firstOrFail();
        $this->assertSame('Ana María', $created->user_name);
        $this->assertSame('López', $created->user_last_name);
        $this->assertSame('Pérez', $created->user_middle_name);
        $this->assertSame('7123456789', $created->user_cel);
        $this->assertSame('inactive', $created->user_status);
        $this->assertTrue($created->roles()->whereKey($studentRole->getKey())->exists());
    }

    public function test_admin_update_keeps_names_in_separate_columns(): void
    {
        $adminRole = $this->role('admin');
        $professorRole = $this->role('professor');
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'user_name' => 'José Luis',
            'user_last_name' => 'Santos',
            'user_middle_name' => 'Mora',
            'user_cel' => '7987654321',
            'email' => 'jose@utbispuebla.edu.mx',
            'role' => 'professor',
            'user_status' => 'active',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users'));

        $user->refresh();
        $this->assertSame('José Luis', $user->user_name);
        $this->assertSame('Santos', $user->user_last_name);
        $this->assertSame('Mora', $user->user_middle_name);
        $this->assertTrue($user->roles()->whereKey($professorRole->getKey())->exists());
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $adminRole = $this->role('admin');
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.users'))
            ->delete(route('admin.users.delete', $admin));

        $response
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('error');
        $this->assertNotNull($admin->fresh());
    }

    public function test_deactivating_a_user_revokes_database_sessions_and_remember_token(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach($this->role('admin'));
        $student = User::factory()->create(['remember_token' => 'persistent-token']);
        $student->roles()->attach($this->role('student'));
        DB::table('sessions')->insert([
            'id' => 'student-session',
            'user_id' => $student->user_id,
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)->patch(route('admin.users.update', $student), [
            'user_name' => $student->user_name,
            'user_last_name' => $student->user_last_name,
            'user_middle_name' => $student->user_middle_name,
            'user_cel' => $student->user_cel,
            'email' => $student->user_email,
            'role' => 'student',
            'user_status' => 'inactive',
        ])->assertRedirect(route('admin.users'));

        $this->assertDatabaseMissing('sessions', ['id' => 'student-session']);
        $this->assertNull($student->fresh()->remember_token);
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate(
            ['role_name' => $name],
            ['role_description' => ucfirst($name)],
        );
    }
}

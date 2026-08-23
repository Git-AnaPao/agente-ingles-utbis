<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@utbispuebla.edu.mx',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@utbispuebla.edu.mx', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_profile_names_and_cellphone_are_updated_separately(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'María Elena',
                'user_last_name' => 'García',
                'user_middle_name' => 'Luna',
                'user_cel' => '7123456789',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('María Elena', $user->user_name);
        $this->assertSame('García', $user->user_last_name);
        $this->assertSame('Luna', $user->user_middle_name);
        $this->assertSame('7123456789', $user->user_cel);
    }

    public function test_google_only_account_hides_local_password_and_deletion_actions(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google-user-id',
            'user_password' => null,
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSee('Cuenta administrada con Google')
            ->assertDontSee('Actualizar contraseña')
            ->assertDontSee('Eliminar definitivamente');
    }

    public function test_google_only_account_cannot_be_deleted_with_a_local_password(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google-user-id',
            'user_password' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password']);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');
        $this->assertNotNull($user->fresh());
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}

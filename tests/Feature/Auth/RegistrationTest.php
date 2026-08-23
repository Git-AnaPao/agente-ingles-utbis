<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'user_last_name' => 'Student',
            'user_cel' => '7123456789',
            'email' => 'test@utbispuebla.edu.mx',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_rejects_suffix_and_subdomains(): void
    {
        foreach (['student@evilutbispuebla.edu.mx', 'student@campus.utbispuebla.edu.mx'] as $email) {
            $this->post('/register', [
                'name' => 'Test',
                'user_last_name' => 'Student',
                'user_cel' => fake()->unique()->numerify('7########'),
                'email' => $email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertSessionHasErrors('email');
        }

        $this->assertDatabaseCount('users', 0);
    }
}

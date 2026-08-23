<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jwt.secret' => str_repeat('a', 64)]);
    }

    public function test_user_uses_the_real_password_column_and_email_verification_contract(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->assertInstanceOf(MustVerifyEmail::class, $user);
        $this->assertSame('user_password', $user->getAuthPasswordName());
        $this->assertFalse($user->hasVerifiedEmail());

        $this->assertTrue($user->markEmailAsVerified());
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_api_login_uses_user_password_and_returns_only_safe_user_fields(): void
    {
        $user = User::factory()->create([
            'user_email' => 'student@utbispuebla.edu.mx',
            'google_id' => 'private-google-id',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => ' STUDENT@UTBISPUEBLA.EDU.MX ',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->user_id)
            ->assertJsonPath('user.email', 'student@utbispuebla.edu.mx')
            ->assertJsonPath('user.email_verified', true);

        $this->assertIsString($response->json('token'));
        $this->assertSame(
            ['id', 'name', 'email', 'role', 'email_verified'],
            array_keys($response->json('user')),
        );
    }

    public function test_inactive_users_are_rejected_by_api_and_web_login_without_disclosing_status(): void
    {
        $user = User::factory()->create([
            'user_email' => 'inactive@utbispuebla.edu.mx',
            'user_status' => 'inactive',
        ]);

        $apiResponse = $this->postJson('/api/auth/login', [
            'email' => $user->user_email,
            'password' => 'password',
        ]);

        $apiResponse
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonMissingPath('token');
        $this->assertSame(
            'Credenciales incorrectas.',
            $apiResponse->json('errors.email.0'),
        );

        $webResponse = $this->post('/login', [
            'email' => $user->user_email,
            'password' => 'password',
        ]);

        $webResponse->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_an_existing_web_session_is_closed_when_the_account_becomes_inactive(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertOk();
        $user->forceFill(['user_status' => 'inactive'])->save();

        $this->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_api_refresh_accepts_an_expired_token_within_the_refresh_window(): void
    {
        config(['jwt.ttl' => 1, 'jwt.refresh_ttl' => 60]);
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->travel(2)->minutes();

        $this->withToken($token)
            ->postJson('/api/auth/refresh')
            ->assertOk()
            ->assertJsonStructure(['token']);
    }
}

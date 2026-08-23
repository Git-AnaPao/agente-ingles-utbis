<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleOAuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_uses_stateful_oauth(): void
    {
        $provider = Mockery::mock();
        $provider->shouldNotReceive('stateless');
        $provider->shouldReceive('with')
            ->once()
            ->with(['hd' => 'utbispuebla.edu.mx', 'prompt' => 'select_account'])
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.test/oauth'));
        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get('/auth/google')
            ->assertRedirect('https://accounts.google.test/oauth');
    }

    public function test_google_callback_rejects_ambiguous_email_and_provider_id_matches(): void
    {
        $emailUser = User::factory()->create([
            'user_email' => 'student@utbispuebla.edu.mx',
            'google_id' => null,
        ]);
        User::factory()->create([
            'user_email' => 'other@utbispuebla.edu.mx',
            'google_id' => 'google-subject-123',
        ]);
        $this->mockGoogleUser('google-subject-123', 'student@utbispuebla.edu.mx');

        $response = $this->get('/auth/google/callback');

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull($emailUser->fresh()->google_id);
    }

    public function test_google_callback_does_not_link_or_login_an_inactive_account(): void
    {
        $user = User::factory()->create([
            'user_email' => 'inactive@utbispuebla.edu.mx',
            'google_id' => null,
            'user_status' => 'inactive',
        ]);
        $this->mockGoogleUser('google-subject-456', $user->user_email);

        $response = $this->get('/auth/google/callback');

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_google_callback_links_only_a_verified_matching_active_account(): void
    {
        $user = User::factory()->create([
            'user_email' => 'matching@utbispuebla.edu.mx',
            'google_id' => null,
            'email_verified_at' => null,
        ]);
        $this->mockGoogleUser('google-subject-789', $user->user_email);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertSame('google-subject-789', $user->google_id);
        $this->assertTrue($user->hasVerifiedEmail());
    }

    private function mockGoogleUser(string $id, string $email): void
    {
        $googleUser = SocialiteUser::fake([
            'id' => $id,
            'email' => $email,
            'name' => 'Google Student',
            'given_name' => 'Google',
            'family_name' => 'Student',
            'email_verified' => true,
        ]);
        $provider = Mockery::mock();
        $provider->shouldNotReceive('stateless');
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }
}

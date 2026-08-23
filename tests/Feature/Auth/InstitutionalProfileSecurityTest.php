<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionalProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_rejects_subdomains_and_suffix_domains(): void
    {
        $user = User::factory()->create([
            'user_email' => 'original@utbispuebla.edu.mx',
            'email_verified_at' => now(),
        ]);

        foreach ([
            'student@campus.utbispuebla.edu.mx',
            'student@utbispuebla.edu.mx.example.com',
        ] as $email) {
            $response = $this
                ->actingAs($user)
                ->from('/profile')
                ->patch('/profile', [
                    'name' => 'Student Name',
                    'email' => $email,
                ]);

            $response
                ->assertRedirect('/profile')
                ->assertSessionHasErrors('email');
            $this->assertSame('original@utbispuebla.edu.mx', $user->fresh()->user_email);
        }
    }

    public function test_profile_accepts_only_the_exact_institutional_domain_and_normalizes_case(): void
    {
        $user = User::factory()->create([
            'user_email' => 'original@utbispuebla.edu.mx',
            'email_verified_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Student Name',
                'email' => ' NEW.STUDENT@UTBISPUEBLA.EDU.MX ',
            ]);

        $response
            ->assertRedirect('/profile')
            ->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame('new.student@utbispuebla.edu.mx', $user->user_email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_google_only_account_cannot_change_its_login_email(): void
    {
        $user = User::factory()->create([
            'user_email' => 'google@utbispuebla.edu.mx',
            'user_password' => null,
            'google_id' => 'google-id',
        ]);

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Google Student',
            'email' => 'changed@utbispuebla.edu.mx',
        ])->assertSessionHasErrors('email');

        $this->assertSame('google@utbispuebla.edu.mx', $user->fresh()->user_email);
    }
}

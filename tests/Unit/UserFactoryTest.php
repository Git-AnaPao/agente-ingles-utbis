<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserFactoryTest extends TestCase
{
    public function test_factory_uses_the_custom_user_columns_and_verified_state(): void
    {
        $user = User::factory()->make();

        $this->assertNotEmpty($user->user_email);
        $this->assertMatchesRegularExpression('/^7\d{8}$/', $user->user_cel);
        $this->assertTrue(Hash::check('password', $user->user_password));
        $this->assertSame('active', $user->user_status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotEmpty($user->remember_token);
    }

    public function test_unverified_state_clears_the_verification_timestamp(): void
    {
        $user = User::factory()->unverified()->make();

        $this->assertNull($user->email_verified_at);
    }
}

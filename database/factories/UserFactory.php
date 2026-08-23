<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'user_email' => fake()->unique()->numerify('student########').'@utbispuebla.edu.mx',
            'user_cel' => fake()->unique()->numerify('7########'),
            'user_password' => static::$password ??= Hash::make('password'),
            'user_name' => fake()->firstName(),
            'user_last_name' => fake()->lastName(),
            'user_middle_name' => fake()->lastName(),
            'user_status' => 'active',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

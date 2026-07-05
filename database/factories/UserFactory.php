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
            'user_email' => fake()->unique()->safeEmail(),
            'user_cel' => fake()->numerify('7########'),
            'user_password' => static::$password ??= Hash::make('password'),
            'user_name' => fake()->firstName(),
            'user_last_name' => fake()->lastName(),
            'user_middle_name' => fake()->lastName(),
            'user_status' => 'active',
        ];
    }
}

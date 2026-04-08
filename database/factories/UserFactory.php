<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement([Gender::Male->value, Gender::Female->value]);

        return [
            'first_name' => fake()->firstName($gender === Gender::Female->value ? 'female' : 'male'),
            'last_name' => fake()->lastName(),
            'phone' => fake()->unique()->numerify('07########'),
            'email' => fake()->boolean(80) ? fake()->unique()->safeEmail() : null,
            'gender' => $gender,
            'is_admin' => false,
            'is_active' => true,
            'is_blocked' => false,
            'blocked_at' => null,
            'blocked_reason' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function male(): static
    {
        return $this->state(fn (): array => [
            'gender' => Gender::Male->value,
            'first_name' => fake()->firstName('male'),
        ]);
    }

    public function female(): static
    {
        return $this->state(fn (): array => [
            'gender' => Gender::Female->value,
            'first_name' => fake()->firstName('female'),
        ]);
    }
}

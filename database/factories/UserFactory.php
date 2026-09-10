<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Representative,
            'job_title' => 'Customer representative',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function administrator(): static
    {
        return $this->state(fn () => ['role' => UserRole::Administrator, 'job_title' => 'Operations director']);
    }

    public function representative(): static
    {
        return $this->state(fn () => ['role' => UserRole::Representative, 'job_title' => 'Customer representative']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /** Paused by an administrator. */
    public function suspended(): static
    {
        return $this->state(fn () => ['suspended_at' => now()->subDay()]);
    }

    /** Access period ran out yesterday. */
    public function expired(): static
    {
        return $this->state(fn () => ['access_expires_at' => now()->subDay()]);
    }
}

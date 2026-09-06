<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Support\Countries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'company' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+## ### ### ####'),
            'city' => fake()->city(),
            'country' => fake()->randomElement(Countries::names()),
            'notifications_enabled' => true,
        ];
    }

    public function sample(): static
    {
        return $this->state(fn () => ['is_sample' => true]);
    }
}

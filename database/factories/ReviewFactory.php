<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_name' => fake()->name(),
            'company' => fake()->company(),
            'location' => fake()->city(),
            'rating' => 5,
            'body' => 'Clear communication and the paperwork was ready before the vessel arrived.',
            'is_published' => true,
            'is_sample' => true,
            'reviewed_on' => now()->subMonths(2),
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}

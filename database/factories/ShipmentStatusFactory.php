<?php

namespace Database\Factories;

use App\Enums\StatusCategory;
use App\Models\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShipmentStatus>
 */
class ShipmentStatusFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'category' => StatusCategory::Milestone,
            'stage' => fake()->numberBetween(1, 14),
            'sort_order' => fake()->numberBetween(1, 14),
            'colour' => 'slate',
            'is_active' => true,
        ];
    }

    public function exception(): static
    {
        return $this->state(fn () => [
            'category' => StatusCategory::Exception,
            'stage' => null,
            'colour' => 'amber',
            'requires_explanation' => true,
        ]);
    }
}

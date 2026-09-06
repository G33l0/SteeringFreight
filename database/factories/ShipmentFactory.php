<?php

namespace Database\Factories;

use App\Enums\ShippingMethod;
use App\Models\Shipment;
use App\Services\TrackingNumberGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tracking_number' => app(TrackingNumberGenerator::class)->generate(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('+## ### ### ####'),
            'origin_city' => fake()->city(),
            'origin_country' => fake()->country(),
            'destination_city' => fake()->city(),
            'destination_country' => fake()->country(),
            'shipping_method' => fake()->randomElement(ShippingMethod::cases()),
            'cargo_description' => 'General cargo, palletised',
            'package_count' => fake()->numberBetween(1, 40),
            'weight_kg' => fake()->randomFloat(2, 50, 24000),
            'estimated_delivery' => now()->addDays(fake()->numberBetween(3, 45)),
            'progress_stage' => 0,
            'status_updated_at' => now(),
        ];
    }

    public function sample(): static
    {
        return $this->state(fn () => ['is_sample' => true]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['archived_at' => now()]);
    }
}

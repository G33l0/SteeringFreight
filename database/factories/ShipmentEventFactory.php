<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentEvent>
 */
class ShipmentEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'shipment_status_id' => ShipmentStatus::factory(),
            'location' => fake()->city(),
            'occurred_at' => now()->subDays(fake()->numberBetween(0, 20)),
            'description' => 'Shipment update recorded by the operations desk.',
            'is_public' => true,
        ];
    }

    public function internal(): static
    {
        return $this->state(fn () => ['is_public' => false, 'internal_note' => 'Internal handling note.']);
    }
}

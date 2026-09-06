<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Enums\ShippingMethod;
use App\Models\QuoteRequest;
use App\Support\Countries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteRequest>
 */
class QuoteRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('+## ### ### ####'),
            'origin_country' => fake()->randomElement(Countries::names()),
            'origin_city' => fake()->city(),
            'destination_country' => fake()->randomElement(Countries::names()),
            'destination_city' => fake()->city(),
            'shipping_method' => fake()->randomElement(ShippingMethod::cases()),
            'cargo_type' => 'Machinery parts',
            'approximate_weight' => '1,200 kg',
            'package_count' => 6,
            'status' => QuoteStatus::New,
        ];
    }
}

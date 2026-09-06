<?php

namespace Database\Factories;

use App\Enums\ConversationStatus;
use App\Models\ChatConversation;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatConversation>
 */
class ChatConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'status' => ConversationStatus::Open,
            'last_message_at' => now(),
        ];
    }
}

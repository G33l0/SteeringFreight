<?php

namespace Database\Factories;

use App\Enums\MessageSender;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_conversation_id' => ChatConversation::factory(),
            'sender_type' => MessageSender::Customer,
            'sender_name' => fake()->name(),
            'body' => 'Could you confirm the expected arrival date?',
        ];
    }

    /** A reply written by a member of staff rather than the customer. */
    public function fromStaff(): static
    {
        return $this->state(fn () => [
            'sender_type' => MessageSender::Staff,
            'sender_name' => 'Operations desk',
            'body' => 'The vessel is on schedule for Friday.',
        ]);
    }
}

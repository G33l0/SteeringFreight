<?php

namespace Tests\Feature\Public;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Shipment;
use App\Notifications\StaffReplyPosted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The customer chat is a window, not a record: it holds nothing beyond the
 * retention window in config/portlane.php, which is 24 hours.
 */
class ChatRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        Notification::fake();
    }

    /**
     * Start a real conversation through the tracking page, so the session that
     * owns it is set up the way a customer's browser would be.
     */
    private function startConversation(Shipment $shipment): ChatConversation
    {
        $this->get(route('track.show', $shipment->tracking_number));
        $this->post(route('track.chat.store', ['tracking_number' => $shipment->tracking_number]), [
            'contact_name' => 'Amara Diallo',
            'contact_email' => 'amara@example.com',
            'body' => 'Could you confirm the delivery window?',
        ]);

        return ChatConversation::latest('id')->firstOrFail();
    }

    public function test_the_retention_window_is_twenty_four_hours_by_default(): void
    {
        $this->assertSame(24, chat_retention_hours());
    }

    public function test_the_purge_command_deletes_conversations_past_the_window(): void
    {
        $shipment = Shipment::factory()->create();

        $stale = ChatConversation::factory()->create([
            'shipment_id' => $shipment->id,
            'last_message_at' => now()->subHours(25),
        ]);
        ChatMessage::factory()->count(2)->create(['chat_conversation_id' => $stale->id]);

        $recent = ChatConversation::factory()->create([
            'shipment_id' => $shipment->id,
            'last_message_at' => now()->subHours(2),
        ]);
        ChatMessage::factory()->create(['chat_conversation_id' => $recent->id]);

        $this->artisan('portlane:purge-chat')->assertSuccessful();

        $this->assertDatabaseMissing('chat_conversations', ['id' => $stale->id]);
        $this->assertDatabaseMissing('chat_messages', ['chat_conversation_id' => $stale->id]);

        // Anything still inside the window is untouched.
        $this->assertDatabaseHas('chat_conversations', ['id' => $recent->id]);
        $this->assertSame(1, ChatMessage::where('chat_conversation_id', $recent->id)->count());
    }

    public function test_a_conversation_past_the_window_is_gone_from_the_tracking_page(): void
    {
        $shipment = Shipment::factory()->create();
        $conversation = $this->startConversation($shipment);

        $this->travel(25)->hours();

        // The thread is not shown, and the customer is offered a fresh start.
        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertDontSee('Could you confirm the delivery window?')
            ->assertSee('Contact shipping team');

        // Neither polling nor replying can reach it, even from the session that
        // created it and even before the scheduled purge has run.
        $this->getJson(route('track.chat.messages', [
            'tracking_number' => $shipment->tracking_number,
            'conversation' => $conversation,
        ]))->assertNotFound();

        $this->post(route('track.chat.reply', [
            'tracking_number' => $shipment->tracking_number,
            'conversation' => $conversation,
        ]), ['body' => 'Anyone still there?'])->assertNotFound();
    }

    public function test_visiting_the_tracking_page_clears_conversations_past_the_window(): void
    {
        $shipment = Shipment::factory()->create();
        $stale = ChatConversation::factory()->create([
            'shipment_id' => $shipment->id,
            'last_message_at' => now()->subHours(30),
        ]);
        ChatMessage::factory()->create(['chat_conversation_id' => $stale->id]);

        // No cron on this host: the sweep runs while the page is used.
        $this->get(route('track.show', $shipment->tracking_number))->assertOk();

        $this->assertDatabaseMissing('chat_conversations', ['id' => $stale->id]);
        $this->assertDatabaseMissing('chat_messages', ['chat_conversation_id' => $stale->id]);
    }

    public function test_staff_cannot_open_a_conversation_past_the_window(): void
    {
        $shipment = Shipment::factory()->create();
        $conversation = ChatConversation::factory()->create([
            'shipment_id' => $shipment->id,
            'last_message_at' => now()->subHours(26),
        ]);
        ChatMessage::factory()->create([
            'chat_conversation_id' => $conversation->id,
            'body' => 'Invoice number and bank details.',
        ]);

        $administrator = $this->administrator();

        $this->actingAs($administrator)
            ->get(route('admin.messages.show', $conversation))
            ->assertNotFound();

        $this->actingAs($administrator)
            ->post(route('admin.messages.reply', $conversation), ['body' => 'Answering an expired thread.'])
            ->assertNotFound();

        // It is not listed in the queue either, and the visit sweeps it away.
        $this->actingAs($administrator)
            ->get(route('admin.messages.index'))
            ->assertOk()
            ->assertDontSee('Invoice number and bank details.');

        $this->assertDatabaseMissing('chat_conversations', ['id' => $conversation->id]);
    }

    public function test_the_reply_notification_does_not_copy_the_message_into_email(): void
    {
        $shipment = Shipment::factory()->create();
        $conversation = $this->startConversation($shipment);

        $notification = new StaffReplyPosted(
            $conversation,
            ChatMessage::factory()->create([
                'chat_conversation_id' => $conversation->id,
                'body' => 'The container is booked on the Friday sailing.',
            ]),
        );

        $rendered = (string) $notification->toMail($conversation)->render();

        $this->assertStringNotContainsString('The container is booked on the Friday sailing.', $rendered);
        $this->assertStringContainsString($shipment->tracking_number, $rendered);
    }
}

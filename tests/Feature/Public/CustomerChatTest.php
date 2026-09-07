<?php

namespace Tests\Feature\Public;

use App\Enums\MessageSender;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        Notification::fake();
    }

    public function test_a_customer_can_start_a_conversation_from_the_tracking_page(): void
    {
        $shipment = Shipment::factory()->create();

        $this->get(route('track.show', $shipment->tracking_number))->assertOk();

        $this->post(route('track.chat.store', ['tracking_number' => $shipment->tracking_number]), [
            'contact_name' => 'Amara Diallo',
            'contact_email' => 'amara@example.com',
            'subject' => 'Delivery window',
            'body' => 'Could you confirm the delivery window for this consignment?',
        ])->assertRedirect();

        $conversation = ChatConversation::firstOrFail();

        $this->assertSame($shipment->id, $conversation->shipment_id);
        $this->assertSame(1, $conversation->unread_for_staff);
        $this->assertSame('Amara Diallo', $conversation->messages()->first()->sender_name);
        $this->assertSame(MessageSender::Customer, $conversation->messages()->first()->sender_type);
    }

    public function test_a_customer_can_reply_and_read_new_messages(): void
    {
        $shipment = Shipment::factory()->create();

        $this->get(route('track.show', $shipment->tracking_number));
        $this->post(route('track.chat.store', ['tracking_number' => $shipment->tracking_number]), [
            'contact_name' => 'Amara Diallo',
            'contact_email' => 'amara@example.com',
            'body' => 'First message about this shipment.',
        ]);

        $conversation = ChatConversation::firstOrFail();

        $this->post(route('track.chat.reply', [
            'tracking_number' => $shipment->tracking_number,
            'conversation' => $conversation,
        ]), ['body' => 'A follow up question.'])->assertRedirect();

        $this->assertSame(2, $conversation->messages()->count());

        $this->getJson(route('track.chat.messages', [
            'tracking_number' => $shipment->tracking_number,
            'conversation' => $conversation,
        ]))->assertOk()->assertJsonCount(2, 'messages');
    }

    public function test_a_conversation_cannot_be_read_without_the_session_that_created_it(): void
    {
        $shipment = Shipment::factory()->create();
        $conversation = ChatConversation::factory()->create(['shipment_id' => $shipment->id]);
        ChatMessage::factory()->create(['chat_conversation_id' => $conversation->id]);

        // Knowing the tracking number is not enough to read somebody else's thread.
        $this->getJson(route('track.chat.messages', [
            'tracking_number' => $shipment->tracking_number,
            'conversation' => $conversation,
        ]))->assertForbidden();

        $this->post(route('track.chat.reply', [
            'tracking_number' => $shipment->tracking_number,
            'conversation' => $conversation,
        ]), ['body' => 'Trying to post into another conversation.'])->assertForbidden();
    }

    public function test_a_conversation_belonging_to_another_shipment_is_not_found(): void
    {
        $shipment = Shipment::factory()->create();
        $otherShipment = Shipment::factory()->create();
        $conversation = ChatConversation::factory()->create(['shipment_id' => $otherShipment->id]);

        $this->getJson(route('track.chat.messages', [
            'tracking_number' => $shipment->tracking_number,
            'conversation' => $conversation,
        ]))->assertNotFound();
    }

    public function test_an_administrator_can_reply_and_the_customer_sees_it(): void
    {
        $shipment = Shipment::factory()->create();

        $this->get(route('track.show', $shipment->tracking_number));
        $this->post(route('track.chat.store', ['tracking_number' => $shipment->tracking_number]), [
            'contact_name' => 'Amara Diallo',
            'contact_email' => 'amara@example.com',
            'body' => 'Question from the customer.',
        ]);

        $conversation = ChatConversation::firstOrFail();
        $staff = $this->administrator();

        $this->actingAs($staff)
            ->post(route('admin.messages.reply', $conversation), ['body' => 'The container is booked on the Friday sailing.'])
            ->assertRedirect(route('admin.messages.show', $conversation));

        $this->assertSame(MessageSender::Staff, $conversation->messages()->get()->last()->sender_type);
        $this->assertSame(0, $conversation->fresh()->unread_for_staff);
        $this->assertSame(1, $conversation->fresh()->unread_for_customer);
        $this->assertDatabaseHas('audit_logs', ['action' => 'chat.staff_message']);

        // Back on the tracking page, the customer sees the reply.
        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertSee('The container is booked on the Friday sailing.');
    }

    public function test_files_cannot_be_sent_through_the_chat(): void
    {
        Storage::fake('local');

        $shipment = Shipment::factory()->create();

        $this->get(route('track.show', $shipment->tracking_number));
        $this->post(route('track.chat.store', ['tracking_number' => $shipment->tracking_number]), [
            'contact_name' => 'Amara Diallo',
            'contact_email' => 'amara@example.com',
            'body' => 'Packing list attached.',
            'attachment' => UploadedFile::fake()->create('packing-list.pdf', 40, 'application/pdf'),
        ])->assertRedirect();

        // The message goes through; the file is ignored and never written.
        $this->assertSame(1, ChatMessage::count());
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_the_tracking_page_says_the_conversation_is_cleared(): void
    {
        $shipment = Shipment::factory()->create();

        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertSee('cleared automatically '.chat_retention_hours().' hours after the last message')
            ->assertSee('files cannot be sent through it');
    }

    public function test_message_sending_is_rate_limited(): void
    {
        $shipment = Shipment::factory()->create();
        $this->get(route('track.show', $shipment->tracking_number));

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post(route('track.chat.store', ['tracking_number' => $shipment->tracking_number]), [
                'contact_name' => 'Amara Diallo',
                'contact_email' => 'amara@example.com',
                'body' => 'Message number '.$attempt,
            ]);
        }

        $this->post(route('track.chat.store', ['tracking_number' => $shipment->tracking_number]), [
            'contact_name' => 'Amara Diallo',
            'contact_email' => 'amara@example.com',
            'body' => 'One message too many.',
        ])->assertStatus(429);
    }
}

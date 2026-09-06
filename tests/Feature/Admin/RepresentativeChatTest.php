<?php

namespace Tests\Feature\Admin;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Shipment;
use App\Notifications\NewCustomerMessage;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RepresentativeChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        Notification::fake();
    }

    private function conversation(array $attributes = []): ChatConversation
    {
        $shipment = Shipment::factory()->create([
            'shipment_status_id' => $this->trackingStatus('in-transit')->id,
        ]);

        $conversation = ChatConversation::factory()->create(['shipment_id' => $shipment->id] + $attributes);
        ChatMessage::factory()->create(['chat_conversation_id' => $conversation->id]);

        return $conversation;
    }

    public function test_a_representative_only_sees_their_own_and_unclaimed_conversations(): void
    {
        $mine = $this->representative(['name' => 'Ada Rep']);
        $other = $this->representative(['name' => 'Bola Rep']);

        $ours = $this->conversation(['assigned_to' => $mine->id, 'contact_name' => 'Assigned To Me']);
        $theirs = $this->conversation(['assigned_to' => $other->id, 'contact_name' => 'Assigned To Bola']);
        $waiting = $this->conversation(['contact_name' => 'Waiting Customer']);

        $this->actingAs($mine)
            ->get(route('admin.messages.index'))
            ->assertOk()
            ->assertSee('Assigned To Me')
            ->assertSee('Waiting Customer')
            ->assertDontSee('Assigned To Bola');

        $this->assertNotNull($ours->fresh());
        $this->assertNotNull($theirs->fresh());
        $this->assertFalse($waiting->fresh()->isAssigned());
    }

    public function test_a_representative_cannot_open_a_conversation_assigned_to_somebody_else(): void
    {
        $mine = $this->representative();
        $other = $this->representative();

        $conversation = $this->conversation(['assigned_to' => $other->id]);

        $this->actingAs($mine)->get(route('admin.messages.show', $conversation))->assertForbidden();

        $this->actingAs($mine)
            ->post(route('admin.messages.reply', $conversation), ['body' => 'Trying to answer somebody else'])
            ->assertForbidden();

        $this->assertSame(1, $conversation->messages()->count());
    }

    public function test_replying_to_an_unclaimed_conversation_assigns_it(): void
    {
        $representative = $this->representative(['name' => 'Ada Rep']);
        $conversation = $this->conversation();

        $this->assertFalse($conversation->isAssigned());

        $this->actingAs($representative)
            ->post(route('admin.messages.reply', $conversation), ['body' => 'We are checking the vessel schedule now.'])
            ->assertRedirect(route('admin.messages.show', $conversation));

        $conversation->refresh();

        $this->assertTrue($conversation->isAssignedTo($representative));
        $this->assertNotNull($conversation->assigned_at);
        $this->assertSame('We are checking the vessel schedule now.', $conversation->messages()->get()->last()->body);
        $this->assertDatabaseHas('audit_logs', ['action' => 'chat.assigned', 'user_id' => $representative->id]);
    }

    public function test_a_representative_can_pick_up_a_waiting_conversation(): void
    {
        $representative = $this->representative();
        $conversation = $this->conversation();

        $this->actingAs($representative)
            ->post(route('admin.messages.claim', $conversation))
            ->assertRedirect();

        $this->assertTrue($conversation->fresh()->isAssignedTo($representative));
    }

    public function test_a_representative_cannot_take_a_conversation_somebody_else_is_handling(): void
    {
        $mine = $this->representative();
        $other = $this->representative();
        $conversation = $this->conversation(['assigned_to' => $other->id]);

        $this->actingAs($mine)->post(route('admin.messages.claim', $conversation))->assertForbidden();

        $this->assertTrue($conversation->fresh()->isAssignedTo($other));
    }

    public function test_only_the_master_admin_can_reassign_a_conversation(): void
    {
        $administrator = $this->administrator();
        $first = $this->representative(['name' => 'Ada Rep']);
        $second = $this->representative(['name' => 'Bola Rep']);

        $conversation = $this->conversation(['assigned_to' => $first->id]);

        $this->actingAs($first)
            ->post(route('admin.messages.assign', $conversation), ['assigned_to' => $second->id])
            ->assertForbidden();

        $this->actingAs($administrator)
            ->post(route('admin.messages.assign', $conversation), ['assigned_to' => $second->id])
            ->assertRedirect();

        $this->assertTrue($conversation->fresh()->isAssignedTo($second));

        // And back to the queue.
        $this->actingAs($administrator)
            ->post(route('admin.messages.assign', $conversation), ['assigned_to' => null])
            ->assertRedirect();

        $this->assertFalse($conversation->fresh()->isAssigned());
        $this->assertDatabaseHas('audit_logs', ['action' => 'chat.unassigned']);
    }

    public function test_the_private_dashboard_lists_their_conversations_and_the_waiting_queue(): void
    {
        $representative = $this->representative(['name' => 'Ada Rep']);
        $other = $this->representative();

        $this->conversation(['assigned_to' => $representative->id, 'contact_name' => 'My Customer']);
        $this->conversation(['contact_name' => 'Waiting Customer']);
        $this->conversation(['assigned_to' => $other->id, 'contact_name' => 'Not My Customer']);

        $this->actingAs($representative)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('My conversations')
            ->assertSee('My Customer')
            ->assertSee('Waiting Customer')
            ->assertDontSee('Not My Customer');
    }

    public function test_a_representative_sees_the_tracking_details_for_the_conversation(): void
    {
        $representative = $this->representative();
        $conversation = $this->conversation(['assigned_to' => $representative->id]);
        $tracking = $conversation->shipment->tracking_number;

        $this->actingAs($representative)
            ->get(route('admin.messages.show', $conversation))
            ->assertOk()
            ->assertSee($tracking)
            ->assertSee('In Transit')
            // Read only: no link into the shipment editor.
            ->assertDontSee(route('admin.shipments.edit', $conversation->shipment), false);
    }

    public function test_several_representatives_work_their_own_customers_side_by_side(): void
    {
        $ada = $this->representative(['name' => 'Ada Rep']);
        $bola = $this->representative(['name' => 'Bola Rep']);

        $first = $this->conversation(['assigned_to' => $ada->id]);
        $second = $this->conversation(['assigned_to' => $ada->id]);
        $third = $this->conversation(['assigned_to' => $bola->id]);

        $this->actingAs($ada)->post(route('admin.messages.reply', $first), ['body' => 'Ada answering the first customer.'])->assertRedirect();
        $this->actingAs($ada)->post(route('admin.messages.reply', $second), ['body' => 'Ada answering the second customer.'])->assertRedirect();
        $this->actingAs($bola)->post(route('admin.messages.reply', $third), ['body' => 'Bola answering their own customer.'])->assertRedirect();

        $this->assertSame(2, ChatConversation::assignedTo($ada)->count());
        $this->assertSame(1, ChatConversation::assignedTo($bola)->count());
        $this->assertSame('Ada Rep', $first->fresh()->messages()->get()->last()->sender_name);
        $this->assertSame('Bola Rep', $third->fresh()->messages()->get()->last()->sender_name);
    }

    public function test_a_new_customer_message_reaches_the_representative_handling_it(): void
    {
        settings()->set('notifications.admin_email', 'operations@portlane.test');

        $representative = $this->representative(['email' => 'ada@portlane.test']);
        $conversation = $this->conversation(['assigned_to' => $representative->id]);
        $shipment = $conversation->shipment;

        $this->get(route('track.show', $shipment->tracking_number));
        session()->put(ChatService::SESSION_KEY, [$conversation->id => $conversation->public_token]);

        $this->post(route('track.chat.reply', [
            'tracking_number' => $shipment->tracking_number,
            'conversation' => $conversation,
        ]), ['body' => 'Any update on the vessel?'])->assertRedirect();

        Notification::assertSentOnDemand(
            NewCustomerMessage::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'ada@portlane.test',
        );
        Notification::assertSentOnDemand(
            NewCustomerMessage::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'operations@portlane.test',
        );
    }

    public function test_the_unread_badge_only_counts_what_the_representative_may_open(): void
    {
        $mine = $this->representative();
        $other = $this->representative();

        $this->conversation(['assigned_to' => $mine->id, 'unread_for_staff' => 2]);
        $this->conversation(['assigned_to' => $other->id, 'unread_for_staff' => 5]);

        $response = $this->actingAs($mine)->get(route('admin.messages.index'));

        $response->assertOk();
        $this->assertSame(2, (int) ChatConversation::forRepresentative($mine)->sum('unread_for_staff'));
    }
}

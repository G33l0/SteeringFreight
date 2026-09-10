<?php

namespace Tests\Feature\Admin;

use App\Enums\ConversationStatus;
use App\Enums\MessageSender;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * What a master admin can see and do in the customer chat.
 *
 * A representative sees only their own queue, which is the point of the role.
 * The person running the business sees all of it: every tracking number's
 * conversation, including the ones a representative is handling, and can answer
 * any of them. The retention window is the one thing that is not overridden —
 * a conversation past it is gone for everybody, the administrator included.
 */
class AdministratorChatOversightTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        Notification::fake();
    }

    private function shipment(): Shipment
    {
        return Shipment::factory()->create([
            'shipment_status_id' => $this->trackingStatus('in-transit')->id,
        ]);
    }

    /**
     * A conversation on a tracking number that a representative is already
     * handling, with a message from each side.
     */
    private function handledConversation(User $rep, string $contact = 'Customer Chidi'): ChatConversation
    {
        $conversation = ChatConversation::factory()->create([
            'shipment_id' => $this->shipment()->id,
            'contact_name' => $contact,
            'assigned_to' => $rep->getKey(),
            'assigned_at' => now(),
        ]);

        ChatMessage::factory()->create([
            'chat_conversation_id' => $conversation->id,
            'sender_type' => MessageSender::Customer,
            'sender_name' => $contact,
            'body' => 'Where is my container right now?',
        ]);

        ChatMessage::factory()->create([
            'chat_conversation_id' => $conversation->id,
            'sender_type' => MessageSender::Staff,
            'user_id' => $rep->getKey(),
            'sender_name' => $rep->name,
            'body' => 'It cleared the port this morning.',
        ]);

        return $conversation;
    }

    public function test_the_administrator_sees_every_conversation_including_other_peoples(): void
    {
        $admin = $this->administrator();
        $ada = $this->representative(['name' => 'Ada Rep']);
        $bola = $this->representative(['name' => 'Bola Rep']);

        $this->handledConversation($ada, 'Customer Of Ada');
        $this->handledConversation($bola, 'Customer Of Bola');

        ChatConversation::factory()->create([
            'shipment_id' => $this->shipment()->id,
            'contact_name' => 'Waiting Customer',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.messages.index'))
            ->assertOk()
            ->assertSee('Customer Of Ada')
            ->assertSee('Customer Of Bola')
            ->assertSee('Waiting Customer');
    }

    public function test_the_administrator_can_read_a_thread_between_a_representative_and_a_customer(): void
    {
        $admin = $this->administrator();
        $ada = $this->representative(['name' => 'Ada Rep']);

        $conversation = $this->handledConversation($ada);

        $this->actingAs($admin)
            ->get(route('admin.messages.show', $conversation))
            ->assertOk()
            ->assertSee('Where is my container right now?')
            ->assertSee('It cleared the port this morning.')
            ->assertSee('Ada Rep');
    }

    public function test_the_administrator_can_reply_to_a_conversation_somebody_else_is_handling(): void
    {
        $admin = $this->administrator(['name' => 'Ngozi Admin']);
        $ada = $this->representative(['name' => 'Ada Rep']);

        $conversation = $this->handledConversation($ada);

        $this->actingAs($admin)
            ->post(route('admin.messages.reply', $conversation), ['body' => 'Adding to that: delivery is booked for Friday.'])
            ->assertRedirect(route('admin.messages.show', $conversation));

        $reply = $conversation->fresh()->messages()->reorder('id', 'desc')->first();

        $this->assertSame('Adding to that: delivery is booked for Friday.', $reply->body);
        $this->assertSame($admin->id, $reply->user_id);
        $this->assertSame(MessageSender::Staff, $reply->sender_type);

        // Answering somebody else's conversation does not take it off them.
        $this->assertSame($ada->id, $conversation->fresh()->assigned_to);
    }

    public function test_the_administrator_can_reply_on_every_tracking_number(): void
    {
        $admin = $this->administrator();
        $ada = $this->representative();

        $conversations = collect([
            $this->handledConversation($ada, 'First Customer'),
            $this->handledConversation($this->representative(), 'Second Customer'),
            ChatConversation::factory()->create([
                'shipment_id' => $this->shipment()->id,
                'contact_name' => 'Third Customer',
            ]),
        ]);

        foreach ($conversations as $conversation) {
            $this->actingAs($admin)
                ->post(route('admin.messages.reply', $conversation), ['body' => 'Noted, thank you.'])
                ->assertRedirect(route('admin.messages.show', $conversation));

            $this->assertSame(
                'Noted, thank you.',
                $conversation->fresh()->messages()->reorder('id', 'desc')->first()->body,
            );
        }
    }

    public function test_the_administrator_can_take_a_conversation_off_a_representative(): void
    {
        $admin = $this->administrator();
        $ada = $this->representative(['name' => 'Ada Rep']);
        $bola = $this->representative(['name' => 'Bola Rep']);

        $conversation = $this->handledConversation($ada);

        $this->actingAs($admin)
            ->post(route('admin.messages.assign', $conversation), ['assigned_to' => $bola->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($bola->id, $conversation->fresh()->assigned_to);

        $this->actingAs($admin)
            ->post(route('admin.messages.assign', $conversation), ['assigned_to' => null])
            ->assertSessionHasNoErrors();

        $this->assertNull($conversation->fresh()->assigned_to);
    }

    public function test_a_representative_still_cannot_read_somebody_elses_thread(): void
    {
        $ada = $this->representative(['name' => 'Ada Rep']);
        $bola = $this->representative(['name' => 'Bola Rep']);

        $conversation = $this->handledConversation($bola, 'Customer Of Bola');

        $this->actingAs($ada)->get(route('admin.messages.show', $conversation))->assertForbidden();
        $this->actingAs($ada)
            ->post(route('admin.messages.reply', $conversation), ['body' => 'Let me take this.'])
            ->assertForbidden();
    }

    public function test_the_retention_window_applies_to_the_administrator_too(): void
    {
        $admin = $this->administrator();
        $ada = $this->representative();

        $conversation = $this->handledConversation($ada);

        $this->travel((int) config('portlane.chat.retention_hours') + 1)->hours();

        $this->actingAs($admin)->get(route('admin.messages.show', $conversation))->assertNotFound();
        $this->actingAs($admin)
            ->post(route('admin.messages.reply', $conversation), ['body' => 'Still here?'])
            ->assertNotFound();
    }

    public function test_a_closed_conversation_is_reopened_before_it_is_answered(): void
    {
        $admin = $this->administrator();
        $ada = $this->representative();

        $conversation = $this->handledConversation($ada);
        $conversation->forceFill(['status' => ConversationStatus::Closed])->save();

        $this->actingAs($admin)
            ->post(route('admin.messages.reply', $conversation), ['body' => 'One more thing.'])
            ->assertForbidden();

        $this->actingAs($admin)->post(route('admin.messages.reopen', $conversation))->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.messages.reply', $conversation), ['body' => 'One more thing.'])
            ->assertRedirect(route('admin.messages.show', $conversation));
    }
}

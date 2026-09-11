<?php

namespace Tests\Feature\Public;

use App\Models\ChatConversation;
use App\Models\Shipment;
use App\Services\ChatService;
use App\Support\AgentNames;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * What the customer sees in the chat: a waiting notice until somebody joins,
 * then one first name for the rest of the conversation.
 *
 * The point of the name is that it stays the same and that it is not the real
 * account, so both are worth proving rather than assuming.
 */
class ChatAgentNameTest extends TestCase
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

    public function test_a_new_conversation_has_nobody_in_it_yet(): void
    {
        $shipment = $this->shipment();

        $this->post(route('track.chat.store', $shipment->tracking_number), [
            'contact_name' => 'Chidi Customer',
            'contact_email' => 'chidi@example.test',
            'body' => 'Where is my container?',
        ])->assertRedirect();

        $conversation = ChatConversation::firstOrFail();

        $this->assertNull($conversation->agent_alias);
        $this->assertFalse($conversation->hasAgent());
    }

    public function test_the_customer_is_told_an_agent_is_coming(): void
    {
        $shipment = $this->shipment();

        $this->post(route('track.chat.store', $shipment->tracking_number), [
            'contact_name' => 'Chidi Customer',
            'contact_email' => 'chidi@example.test',
            'body' => 'Where is my container?',
        ]);

        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertSee('A live agent will join you shortly');
    }

    public function test_replying_gives_the_conversation_a_first_name(): void
    {
        $rep = $this->representative(['name' => 'Adaeze Okonkwo']);
        $conversation = ChatConversation::factory()->create(['shipment_id' => $this->shipment()->id]);

        app(ChatService::class)->addStaffMessage($conversation, 'It cleared this morning.', $rep);

        $conversation->refresh();

        $this->assertNotNull($conversation->agent_alias);
        $this->assertContains($conversation->agent_alias, AgentNames::all());
        $this->assertNotSame($rep->name, $conversation->agent_alias);
    }

    public function test_assigning_a_representative_counts_as_joining(): void
    {
        $admin = $this->administrator();
        $rep = $this->representative();
        $conversation = ChatConversation::factory()->create(['shipment_id' => $this->shipment()->id]);

        app(ChatService::class)->assign($conversation, $rep, $admin);

        $this->assertNotNull($conversation->fresh()->agent_alias);
    }

    public function test_the_name_does_not_change_once_it_is_set(): void
    {
        $chat = app(ChatService::class);
        $first = $this->representative();
        $second = $this->representative();
        $conversation = ChatConversation::factory()->create(['shipment_id' => $this->shipment()->id]);

        $chat->addStaffMessage($conversation, 'First reply.', $first);
        $alias = $conversation->fresh()->agent_alias;

        // A second member of staff picking it up must not change who the
        // customer thinks they have been talking to.
        $chat->addStaffMessage($conversation, 'Second reply.', $second);
        $chat->assign($conversation, $second, $this->administrator());

        $this->assertSame($alias, $conversation->fresh()->agent_alias);
    }

    public function test_the_customer_never_sees_the_real_member_of_staff(): void
    {
        $shipment = $this->shipment();
        $rep = $this->representative(['name' => 'Adaeze Okonkwo']);

        $this->post(route('track.chat.store', $shipment->tracking_number), [
            'contact_name' => 'Chidi Customer',
            'contact_email' => 'chidi@example.test',
            'body' => 'Where is my container?',
        ]);

        $conversation = ChatConversation::firstOrFail();
        app(ChatService::class)->addStaffMessage($conversation, 'It cleared this morning.', $rep);

        $alias = $conversation->fresh()->agent_alias;

        $page = $this->get(route('track.show', $shipment->tracking_number));
        $page->assertOk()->assertSee($alias)->assertDontSee('Adaeze Okonkwo');

        $this->getJson(route('track.chat.messages', [
            'tracking_number' => $shipment->tracking_number,
            'conversation' => $conversation,
        ]))
            ->assertOk()
            ->assertJsonPath('agent', $alias)
            ->assertJsonPath('waiting', false)
            ->assertJsonPath('messages.1.sender', $alias)
            ->assertDontSee('Adaeze Okonkwo');
    }

    public function test_the_polling_endpoint_reports_the_wait(): void
    {
        $shipment = $this->shipment();

        $this->post(route('track.chat.store', $shipment->tracking_number), [
            'contact_name' => 'Chidi Customer',
            'contact_email' => 'chidi@example.test',
            'body' => 'Where is my container?',
        ]);

        $conversation = ChatConversation::firstOrFail();

        $this->getJson(route('track.chat.messages', [
            'tracking_number' => $shipment->tracking_number,
            'conversation' => $conversation,
        ]))
            ->assertOk()
            ->assertJsonPath('waiting', true)
            ->assertJsonPath('agent', null);
    }

    public function test_two_open_conversations_get_different_names(): void
    {
        $chat = app(ChatService::class);
        $rep = $this->representative();

        $names = collect(range(1, 8))->map(function () use ($chat, $rep) {
            $conversation = ChatConversation::factory()->create(['shipment_id' => $this->shipment()->id]);
            $chat->addStaffMessage($conversation, 'Hello.', $rep);

            return $conversation->fresh()->agent_alias;
        });

        $this->assertSame($names->count(), $names->unique()->count());
    }

    public function test_the_name_list_is_fifty_plain_first_names(): void
    {
        $names = AgentNames::all();

        $this->assertCount(50, $names);
        $this->assertSame($names, array_values(array_unique($names)));

        foreach ($names as $name) {
            $this->assertStringNotContainsString(' ', $name, "{$name} should be a first name only");
        }
    }
}

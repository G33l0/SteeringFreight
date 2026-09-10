<?php

namespace Tests\Feature\Admin;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Pausing a representative, and the access period that pauses one on its own.
 *
 * The promise being tested is a narrow one and worth stating plainly: a
 * suspended account keeps its history and can still sign in to read why it is
 * suspended, and can do nothing else at all — no conversation, no shipment, no
 * tracking number.
 */
class StaffSuspensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        Notification::fake();
    }

    private function conversationFor(?User $assignee = null): ChatConversation
    {
        $shipment = Shipment::factory()->create([
            'shipment_status_id' => $this->trackingStatus('in-transit')->id,
        ]);

        $conversation = ChatConversation::factory()->create([
            'shipment_id' => $shipment->id,
            'assigned_to' => $assignee?->getKey(),
            'assigned_at' => $assignee ? now() : null,
        ]);

        ChatMessage::factory()->create(['chat_conversation_id' => $conversation->id]);

        return $conversation;
    }

    public function test_the_staff_list_shows_what_each_account_can_do(): void
    {
        $admin = $this->administrator();
        $this->representative(['name' => 'Working Rep']);
        User::factory()->representative()->suspended()->create(['name' => 'Paused Rep']);
        User::factory()->representative()->expired()->create(['name' => 'Expired Rep']);
        User::factory()->representative()->inactive()->create(['name' => 'Disabled Rep']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Working Rep')
            ->assertSee('Paused Rep')
            ->assertSee('Expired Rep')
            ->assertSee('Disabled Rep')
            ->assertSee('Paused')
            ->assertSee('Expired')
            ->assertSee('Disabled')
            ->assertSee('Resume');
    }

    public function test_an_administrator_can_pause_a_representative(): void
    {
        $admin = $this->administrator();
        $rep = $this->representative(['name' => 'Ada Rep']);

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $rep))
            ->assertRedirect();

        $this->assertTrue($rep->fresh()->isSuspended());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.suspended', 'auditable_id' => $rep->id]);
    }

    public function test_a_paused_representative_is_sent_to_the_renewal_notice(): void
    {
        $rep = $this->representative();
        $rep->forceFill(['suspended_at' => now()])->save();

        foreach (['admin.dashboard', 'admin.messages.index', 'admin.profile.edit'] as $route) {
            $this->actingAs($rep)->get(route($route))->assertRedirect(route('admin.suspended'));
        }

        $this->actingAs($rep)
            ->get(route('admin.suspended'))
            ->assertOk()
            ->assertSee('Contact the administrator', false);
    }

    public function test_the_renewal_notice_is_wording_the_administrator_controls(): void
    {
        $this->setSetting('security.renewal_note', 'Your quarterly subscription is due. Email the desk to renew.');

        $rep = User::factory()->representative()->suspended()->create();

        $this->actingAs($rep)
            ->get(route('admin.suspended'))
            ->assertOk()
            ->assertSee('Your quarterly subscription is due.');
    }

    public function test_a_paused_representative_cannot_touch_a_conversation(): void
    {
        $rep = User::factory()->representative()->suspended()->create();
        $conversation = $this->conversationFor($rep);

        $this->actingAs($rep)
            ->get(route('admin.messages.show', $conversation))
            ->assertRedirect(route('admin.suspended'));

        $this->actingAs($rep)
            ->post(route('admin.messages.reply', $conversation), ['body' => 'Let me help.'])
            ->assertRedirect(route('admin.suspended'));

        $this->assertSame(1, $conversation->fresh()->messages()->count());
    }

    public function test_a_paused_representative_cannot_touch_a_shipment_or_its_tracking(): void
    {
        $rep = User::factory()->representative()->suspended()->create();
        $shipment = Shipment::factory()->create([
            'shipment_status_id' => $this->trackingStatus('in-transit')->id,
        ]);

        $before = $shipment->tracking_number;

        $this->actingAs($rep)->get(route('admin.shipments.index'))->assertRedirect(route('admin.suspended'));
        $this->actingAs($rep)->get(route('admin.shipments.edit', $shipment))->assertRedirect(route('admin.suspended'));

        $this->actingAs($rep)
            ->post(route('admin.shipments.events.store', $shipment), [
                'shipment_status_id' => $this->trackingStatus('delivered')->id,
                'happened_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('admin.suspended'));

        $shipment->refresh();
        $this->assertSame($before, $shipment->tracking_number);
        $this->assertSame(0, $shipment->events()->count());
    }

    public function test_pausing_takes_away_every_permission_at_once(): void
    {
        $rep = $this->representative();

        $this->assertTrue($rep->hasPermission('chat.reply'));

        $rep->forceFill(['suspended_at' => now()])->save();

        $this->assertFalse($rep->fresh()->hasPermission('chat.reply'));
        $this->assertFalse($rep->fresh()->hasPermission('chat.view'));
    }

    public function test_an_access_period_suspends_the_account_when_it_runs_out(): void
    {
        $rep = $this->representative(['access_expires_at' => now()->addDays(2)]);

        $this->actingAs($rep)->get(route('admin.messages.index'))->assertOk();

        $this->travel(3)->days();

        $rep = $rep->fresh();
        $this->assertTrue($rep->isSuspended());
        $this->assertSame('expired', $rep->suspensionReason());

        $this->actingAs($rep)->get(route('admin.messages.index'))->assertRedirect(route('admin.suspended'));
    }

    public function test_resuming_an_expired_account_gives_it_a_new_period(): void
    {
        $this->setSetting('security.access_days', 30);

        $admin = $this->administrator();
        $rep = User::factory()->representative()->expired()->create();

        $this->actingAs($admin)->post(route('admin.users.resume', $rep))->assertRedirect();

        $rep->refresh();
        $this->assertFalse($rep->isSuspended());
        $this->assertTrue($rep->access_expires_at->isAfter(now()->addDays(29)));

        $this->actingAs($rep)->get(route('admin.messages.index'))->assertOk();
    }

    public function test_resuming_a_paused_account_lets_it_straight_back_in(): void
    {
        $admin = $this->administrator();
        $rep = User::factory()->representative()->suspended()->create();

        $this->actingAs($admin)->post(route('admin.users.resume', $rep))->assertRedirect();

        $this->assertFalse($rep->fresh()->isSuspended());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.resumed', 'auditable_id' => $rep->id]);
        $this->actingAs($rep->fresh())->get(route('admin.messages.index'))->assertOk();
    }

    public function test_a_paused_account_can_still_sign_in_but_lands_on_the_notice(): void
    {
        $rep = User::factory()->representative()->suspended()->create([
            'password' => Hash::make('correct-horse-battery'),
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $rep->email,
            'password' => 'correct-horse-battery',
        ])->assertRedirect(route('admin.suspended'));

        $this->assertAuthenticatedAs($rep);
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.suspended'));
    }

    public function test_an_administrator_can_set_the_access_period_when_creating_a_representative(): void
    {
        $admin = $this->administrator();
        $ends = now()->addDays(45)->toDateString();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Bola Rep',
            'email' => 'bola@example.test',
            'role' => 'representative',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'is_active' => '1',
            'access_expires_at' => $ends,
        ])->assertRedirect(route('admin.users.index'));

        $created = User::where('email', 'bola@example.test')->firstOrFail();
        $this->assertSame($ends, $created->access_expires_at->toDateString());
        $this->assertFalse($created->isSuspended());
    }

    public function test_the_new_account_form_offers_the_default_access_period(): void
    {
        $this->setSetting('security.access_days', 14);

        $this->actingAs($this->administrator())
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee(now()->addDays(14)->toDateString(), false);
    }

    public function test_an_account_cannot_be_created_already_expired(): void
    {
        $this->actingAs($this->administrator())->post(route('admin.users.store'), [
            'name' => 'Bola Rep',
            'email' => 'bola@example.test',
            'role' => 'representative',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'access_expires_at' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors('access_expires_at');

        $this->assertDatabaseMissing('users', ['email' => 'bola@example.test']);
    }

    public function test_an_administrator_can_delete_a_representative(): void
    {
        $admin = $this->administrator();
        $rep = $this->representative();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $rep))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $rep->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deleted']);
    }

    public function test_the_panel_is_never_left_without_a_working_administrator(): void
    {
        $keeper = $this->administrator(['name' => 'Keeper']);
        $spare = $this->administrator(['name' => 'Spare']);

        // While two are usable either one can be paused.
        $this->actingAs($spare)->post(route('admin.users.suspend', $keeper))->assertRedirect();
        $this->assertTrue($keeper->fresh()->isSuspended());

        // The spare is now the only administrator who can open the panel, and
        // cannot pause or delete itself out of it. The paused one cannot act
        // at all: it is sent to the renewal notice like anybody else.
        $this->actingAs($spare)->post(route('admin.users.suspend', $spare))->assertForbidden();
        $this->actingAs($spare)->delete(route('admin.users.destroy', $spare))->assertForbidden();
        $this->actingAs($keeper->fresh())->post(route('admin.users.suspend', $spare))
            ->assertRedirect(route('admin.suspended'));

        $this->assertTrue(User::query()->where('role', 'administrator')->usable()->exists());
        $this->assertFalse($spare->fresh()->isSuspended());
    }

    public function test_an_administrator_cannot_pause_or_delete_themselves(): void
    {
        $admin = $this->administrator();
        $this->administrator();

        $this->actingAs($admin)->post(route('admin.users.suspend', $admin))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertForbidden();

        $this->assertFalse($admin->fresh()->isSuspended());
    }

    public function test_a_representative_cannot_pause_anybody(): void
    {
        $rep = $this->representative();
        $other = $this->representative();

        $this->actingAs($rep)->post(route('admin.users.suspend', $other))->assertForbidden();
        $this->actingAs($rep)->post(route('admin.users.resume', $other))->assertForbidden();

        $this->assertFalse($other->fresh()->isSuspended());
    }

    public function test_a_suspended_representative_is_not_offered_a_conversation(): void
    {
        $admin = $this->administrator();
        $paused = User::factory()->representative()->suspended()->create(['name' => 'Paused Rep']);
        $working = $this->representative(['name' => 'Working Rep']);

        $conversation = $this->conversationFor();

        $this->actingAs($admin)
            ->get(route('admin.messages.show', $conversation))
            ->assertOk()
            ->assertSee('Working Rep')
            ->assertDontSee('Paused Rep');

        $this->actingAs($admin)
            ->post(route('admin.messages.assign', $conversation), ['assigned_to' => $paused->id])
            ->assertSessionHasErrors('assigned_to');

        $this->assertNull($conversation->fresh()->assigned_to);

        $this->actingAs($admin)
            ->post(route('admin.messages.assign', $conversation), ['assigned_to' => $working->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($working->id, $conversation->fresh()->assigned_to);
    }

    public function test_a_disabled_account_is_signed_out_rather_than_shown_the_notice(): void
    {
        $rep = $this->representative();

        $this->actingAs($rep);
        $rep->forceFill(['is_active' => false])->save();

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_the_notice_is_not_shown_to_somebody_who_is_not_suspended(): void
    {
        $this->actingAs($this->representative())
            ->get(route('admin.suspended'))
            ->assertRedirect(route('admin.dashboard'));
    }
}

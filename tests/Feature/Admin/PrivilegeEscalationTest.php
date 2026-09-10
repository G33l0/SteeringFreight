<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\ChatConversation;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Deliberate attempts to give a customer representative more than their role
 * allows. Every one of these should fail; the point of the file is that the
 * failure is proven rather than assumed.
 */
class PrivilegeEscalationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        Notification::fake();
    }

    public function test_a_representative_cannot_reach_the_staff_accounts_screen(): void
    {
        $rep = $this->representative();

        $this->actingAs($rep)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($rep)->get(route('admin.users.create'))->assertForbidden();
        $this->actingAs($rep)->get(route('admin.users.edit', $rep))->assertForbidden();
    }

    public function test_a_representative_cannot_create_an_account_of_any_kind(): void
    {
        $rep = $this->representative();

        $this->actingAs($rep)->post(route('admin.users.store'), [
            'name' => 'Back Door',
            'email' => 'back@door.test',
            'role' => UserRole::Administrator->value,
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'back@door.test']);
    }

    public function test_a_representative_cannot_promote_themselves_through_the_accounts_screen(): void
    {
        $rep = $this->representative();

        $this->actingAs($rep)->put(route('admin.users.update', $rep), [
            'name' => $rep->name,
            'email' => $rep->email,
            'role' => UserRole::Administrator->value,
        ])->assertForbidden();

        $this->assertSame(UserRole::Representative, $rep->fresh()->role);
    }

    public function test_a_representative_cannot_promote_themselves_through_their_own_profile(): void
    {
        $rep = $this->representative();

        // The profile form does not offer a role field; this posts one anyway.
        $this->actingAs($rep)->put(route('admin.profile.update'), [
            'name' => 'Still A Representative',
            'email' => $rep->email,
            'role' => UserRole::Administrator->value,
            'is_active' => true,
        ])->assertRedirect();

        $rep->refresh();

        $this->assertSame('Still A Representative', $rep->name, 'The profile update should still work.');
        $this->assertSame(UserRole::Representative, $rep->role, 'Role must not be settable from the profile form.');
    }

    public function test_a_representative_cannot_deactivate_or_delete_anybody(): void
    {
        $rep = $this->representative();
        $admin = $this->administrator();

        $this->actingAs($rep)->delete(route('admin.users.destroy', $admin))->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_a_representative_is_kept_out_of_every_master_admin_area(): void
    {
        $rep = $this->representative();
        $shipment = Shipment::factory()->create();

        foreach ([
            route('admin.shipments.index'),
            route('admin.shipments.create'),
            route('admin.shipments.show', $shipment),
            route('admin.customers.index'),
            route('admin.statuses.index'),
            route('admin.settings.edit'),
            route('admin.audit-logs.index'),
            route('admin.documents.index'),
            route('admin.reviews.index'),
            route('admin.services.index'),
            route('admin.pages.index'),
            route('admin.faqs.index'),
            route('admin.quotes.index'),
            route('admin.contact-messages.index'),
        ] as $url) {
            $this->actingAs($rep)->get($url)->assertForbidden();
        }
    }

    public function test_a_representative_cannot_create_or_change_a_shipment(): void
    {
        $rep = $this->representative();
        $shipment = Shipment::factory()->create();

        $this->actingAs($rep)->post(route('admin.shipments.store'), [
            'customer_name' => 'Invented Consignee',
        ])->assertForbidden();

        $this->actingAs($rep)->put(route('admin.shipments.update', $shipment), [
            'customer_name' => 'Rewritten',
        ])->assertForbidden();

        $this->actingAs($rep)->post(route('admin.shipments.events.store', $shipment), [
            'shipment_status_id' => $this->trackingStatus('booking-confirmed')->id,
            'description' => 'A tracking update that should never appear.',
            'occurred_at' => now()->toDateTimeString(),
        ])->assertForbidden();

        $this->assertDatabaseMissing('shipments', ['customer_name' => 'Invented Consignee']);
        $this->assertSame(0, $shipment->events()->count());
    }

    public function test_a_representative_cannot_change_site_settings(): void
    {
        $rep = $this->representative();

        $this->actingAs($rep)
            ->put(route('admin.settings.update', 'company'), ['company.name' => 'Hijacked Freight'])
            ->assertForbidden();

        $this->assertNotSame('Hijacked Freight', setting('company.name'));
    }

    public function test_a_representative_cannot_open_a_conversation_belonging_to_another_representative(): void
    {
        $mine = $this->representative();
        $theirs = $this->representative(['email' => 'other@example.test']);

        $conversation = ChatConversation::factory()->create([
            'shipment_id' => Shipment::factory()->create()->id,
            'assigned_to' => $theirs->id,
            'assigned_at' => now(),
            'last_message_at' => now(),
        ]);

        $this->actingAs($mine)->get(route('admin.messages.show', $conversation))->assertForbidden();

        $this->actingAs($mine)
            ->post(route('admin.messages.reply', $conversation), ['body' => 'Reading somebody else post.'])
            ->assertForbidden();

        $this->actingAs($mine)
            ->post(route('admin.messages.assign', $conversation), ['assigned_to' => $mine->id])
            ->assertForbidden();

        $this->assertSame($theirs->id, $conversation->fresh()->assigned_to);
    }

    public function test_a_deactivated_representative_loses_access_immediately(): void
    {
        $rep = $this->representative();

        $this->actingAs($rep)->get(route('admin.messages.index'))->assertOk();

        $rep->forceFill(['is_active' => false])->save();

        $this->actingAs($rep)->get(route('admin.messages.index'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_a_representative_can_do_the_two_things_they_are_meant_to(): void
    {
        $rep = $this->representative();

        // Their own profile.
        $this->actingAs($rep)->get(route('admin.profile.edit'))->assertOk();
        $this->actingAs($rep)->put(route('admin.profile.update'), [
            'name' => 'Chinelo Adeyemi',
            'email' => $rep->email,
            'job_title' => 'Customer representative',
        ])->assertRedirect();
        $this->assertSame('Chinelo Adeyemi', $rep->fresh()->name);

        // Their own password, with the current one required.
        $this->actingAs($rep)->put(route('admin.profile.password'), [
            'current_password' => 'wrong-password',
            'password' => 'An0ther-Str0ng!',
            'password_confirmation' => 'An0ther-Str0ng!',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($rep)->put(route('admin.profile.password'), [
            'current_password' => 'password',
            'password' => 'An0ther-Str0ng!',
            'password_confirmation' => 'An0ther-Str0ng!',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('An0ther-Str0ng!', $rep->fresh()->password));
    }
}

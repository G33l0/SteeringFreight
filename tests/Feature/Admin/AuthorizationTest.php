<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    public function test_a_representative_cannot_reach_the_shipment_screens(): void
    {
        $representative = $this->representative();
        $shipment = Shipment::factory()->create();

        $this->actingAs($representative)->get(route('admin.shipments.index'))->assertForbidden();
        $this->actingAs($representative)->get(route('admin.shipments.create'))->assertForbidden();
        $this->actingAs($representative)->get(route('admin.shipments.show', $shipment))->assertForbidden();
        $this->actingAs($representative)->get(route('admin.shipments.edit', $shipment))->assertForbidden();
    }

    public function test_a_representative_cannot_create_or_change_a_shipment(): void
    {
        $representative = $this->representative();
        $shipment = Shipment::factory()->create(['customer_name' => 'Original name']);

        $this->actingAs($representative)
            ->post(route('admin.shipments.store'), ['customer_name' => 'Should not be created'])
            ->assertForbidden();

        $this->actingAs($representative)
            ->put(route('admin.shipments.update', $shipment), ['customer_name' => 'Changed'])
            ->assertForbidden();

        $this->assertSame(1, Shipment::count());
        $this->assertSame('Original name', $shipment->fresh()->customer_name);
    }

    public function test_a_representative_cannot_add_or_change_tracking_updates(): void
    {
        $representative = $this->representative();
        $shipment = Shipment::factory()->create();

        $this->actingAs($representative)
            ->post(route('admin.shipments.events.store', $shipment), [
                'shipment_status_id' => $this->trackingStatus('in-transit')->id,
                'occurred_at' => now()->toDateTimeString(),
                'description' => 'Should not be recorded.',
            ])
            ->assertForbidden();

        $this->assertSame(0, $shipment->events()->count());
    }

    public function test_a_representative_cannot_change_the_tracking_statuses(): void
    {
        $representative = $this->representative();
        $status = $this->trackingStatus('in-transit');

        $this->actingAs($representative)->get(route('admin.statuses.index'))->assertForbidden();
        $this->actingAs($representative)->get(route('admin.statuses.edit', $status))->assertForbidden();
        $this->actingAs($representative)
            ->put(route('admin.statuses.update', $status), ['name' => 'Renamed', 'category' => 'milestone', 'colour' => 'blue'])
            ->assertForbidden();

        $this->assertSame('In Transit', $status->fresh()->name);
    }

    public function test_a_representative_cannot_reach_settings_users_content_or_audit_logs(): void
    {
        $representative = $this->representative();

        foreach ([
            route('admin.settings.edit'),
            route('admin.users.index'),
            route('admin.audit-logs.index'),
            route('admin.services.index'),
            route('admin.pages.index'),
            route('admin.reviews.index'),
            route('admin.faqs.index'),
            route('admin.customers.index'),
            route('admin.documents.index'),
            route('admin.quotes.index'),
            route('admin.contact-messages.index'),
        ] as $url) {
            $this->actingAs($representative)->get($url)->assertForbidden();
        }
    }

    public function test_a_representative_can_reach_their_own_dashboard_and_messages(): void
    {
        $representative = $this->representative();

        $this->actingAs($representative)->get(route('admin.dashboard'))->assertOk()->assertSee('My conversations');
        $this->actingAs($representative)->get(route('admin.messages.index'))->assertOk();
        $this->actingAs($representative)->get(route('admin.profile.edit'))->assertOk();
    }

    public function test_the_master_admin_can_reach_everything(): void
    {
        $administrator = $this->administrator();

        foreach ([
            route('admin.dashboard'),
            route('admin.shipments.index'),
            route('admin.statuses.index'),
            route('admin.messages.index'),
            route('admin.settings.edit'),
            route('admin.users.index'),
            route('admin.audit-logs.index'),
        ] as $url) {
            $this->actingAs($administrator)->get($url)->assertOk();
        }
    }

    public function test_an_archived_shipment_cannot_be_edited(): void
    {
        $shipment = Shipment::factory()->archived()->create();

        $this->actingAs($this->administrator())
            ->get(route('admin.shipments.edit', $shipment))
            ->assertForbidden();
    }

    public function test_an_administrator_cannot_delete_their_own_account(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)
            ->delete(route('admin.users.destroy', $administrator))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $administrator->id]);
    }

    public function test_the_last_active_master_admin_cannot_be_removed(): void
    {
        $administrator = $this->administrator();
        $second = User::factory()->administrator()->create();

        // Removing one of two master admins is allowed.
        $this->actingAs($administrator)->delete(route('admin.users.destroy', $second))->assertRedirect();

        $representative = User::factory()->create(['role' => UserRole::Representative]);

        $this->actingAs($representative);
        $this->delete(route('admin.users.destroy', $administrator))->assertForbidden();
    }
}

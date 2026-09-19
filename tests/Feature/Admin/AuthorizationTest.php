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

    /**
     * A representative now works on shipments — but only the ones they raised
     * or were handed. Somebody else's is as closed to them as it ever was.
     */
    public function test_a_representative_cannot_reach_somebody_elses_shipment(): void
    {
        $representative = $this->representative();
        $shipment = Shipment::factory()->create(['created_by' => $this->representative()->id]);

        $this->actingAs($representative)->get(route('admin.shipments.show', $shipment))->assertForbidden();
        $this->actingAs($representative)->get(route('admin.shipments.edit', $shipment))->assertForbidden();
    }

    public function test_a_representative_reaches_the_shipment_screens_for_their_own_work(): void
    {
        $representative = $this->representative(['tracking_quota' => 5]);
        $mine = Shipment::factory()->create(['created_by' => $representative->id]);

        $this->actingAs($representative)->get(route('admin.shipments.index'))->assertOk();
        $this->actingAs($representative)->get(route('admin.shipments.create'))->assertOk();
        $this->actingAs($representative)->get(route('admin.shipments.show', $mine))->assertOk();
        $this->actingAs($representative)->get(route('admin.shipments.edit', $mine))->assertOk();
    }

    public function test_a_representative_cannot_change_the_tracking_statuses(): void
    {
        $representative = $this->representative();
        $status = $this->trackingStatus('in-transit');

        // Reading the list is needed to set a status on their own shipment.
        $this->actingAs($representative)->get(route('admin.statuses.index'))->assertOk();

        // Changing the list is not.
        $this->actingAs($representative)->get(route('admin.statuses.create'))->assertForbidden();
        $this->actingAs($representative)
            ->put(route('admin.statuses.update', $status), ['name' => 'Renamed', 'category' => 'milestone'])
            ->assertForbidden();
        $this->actingAs($representative)->delete(route('admin.statuses.destroy', $status))->assertForbidden();

        $this->assertSame('In Transit', $status->fresh()->name);
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

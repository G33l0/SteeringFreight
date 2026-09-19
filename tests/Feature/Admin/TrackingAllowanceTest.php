<?php

namespace Tests\Feature\Admin;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * A customer representative raising and updating tracking, within an allowance.
 *
 * The allowance is a count of tracking numbers the account has put into the
 * world, not a rate. When it runs out the representative asks the administrator
 * to raise it, which is the conversation the business wanted to have.
 *
 * Everything here is also a test of the boundary: a representative works on
 * what they raised and what they were handed, and on nothing else.
 */
class TrackingAllowanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        Notification::fake();
    }

    private function shipmentFor(?User $owner = null, array $attributes = []): Shipment
    {
        return Shipment::factory()->create($attributes + [
            'shipment_status_id' => $this->trackingStatus('in-transit')->id,
            'created_by' => $owner?->getKey(),
        ]);
    }

    /** @return array<string, mixed> */
    private function validShipment(array $overrides = []): array
    {
        return $overrides + [
            'customer_name' => 'Chidi Customer',
            'origin_country' => 'Nigeria',
            'origin_city' => 'Lagos',
            'destination_country' => 'Netherlands',
            'destination_city' => 'Rotterdam',
            'shipping_method' => 'sea_fcl',
            'shipment_status_id' => $this->trackingStatus('booking-confirmed')->id,
        ];
    }

    public function test_a_representative_can_raise_a_tracking_number(): void
    {
        $rep = $this->representative(['tracking_quota' => 5]);

        $this->actingAs($rep)
            ->post(route('admin.shipments.store'), $this->validShipment())
            ->assertRedirect();

        $shipment = Shipment::firstOrFail();

        $this->assertSame($rep->id, $shipment->created_by);
        $this->assertSame(1, $rep->fresh()->trackingUsed());
        $this->assertSame(4, $rep->fresh()->trackingRemaining());
    }

    public function test_the_allowance_runs_out_at_five(): void
    {
        $rep = $this->representative(['tracking_quota' => 5]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($rep)->post(route('admin.shipments.store'), $this->validShipment());
        }

        $this->assertSame(5, Shipment::count());
        $this->assertSame(0, $rep->fresh()->trackingRemaining());

        // The sixth never reaches the controller: the policy has already shut
        // the screen, which is the right answer to give somebody with nothing
        // left rather than a form that accepts and then rejects.
        $this->actingAs($rep->fresh())
            ->post(route('admin.shipments.store'), $this->validShipment())
            ->assertForbidden();

        $this->assertSame(5, Shipment::count());
    }

    public function test_the_create_screen_closes_once_the_allowance_is_spent(): void
    {
        $rep = $this->representative(['tracking_quota' => 1]);
        $this->shipmentFor($rep);

        $this->actingAs($rep->fresh())->get(route('admin.shipments.create'))->assertForbidden();
    }

    public function test_the_administrator_can_raise_the_allowance(): void
    {
        $admin = $this->administrator();
        $rep = $this->representative(['tracking_quota' => 5, 'name' => 'Ada Rep']);

        $this->actingAs($admin)->put(route('admin.users.update', $rep), [
            'name' => $rep->name,
            'email' => $rep->email,
            'role' => 'representative',
            'is_active' => '1',
            'tracking_quota' => 25,
        ])->assertRedirect();

        $this->assertSame(25, $rep->fresh()->tracking_quota);
        $this->assertTrue($rep->fresh()->canRaiseTracking());
    }

    public function test_a_master_admin_is_never_limited(): void
    {
        $admin = $this->administrator(['tracking_quota' => 0]);

        $this->assertTrue($admin->canRaiseTracking());

        $this->actingAs($admin)
            ->post(route('admin.shipments.store'), $this->validShipment())
            ->assertRedirect();

        $this->assertSame(1, Shipment::count());
    }

    public function test_a_representative_sees_only_what_they_raised_or_were_handed(): void
    {
        $rep = $this->representative();
        $other = $this->representative();

        $mine = $this->shipmentFor($rep, ['customer_name' => 'Mine Customer']);
        $handed = $this->shipmentFor($other, ['customer_name' => 'Handed Customer', 'assigned_to' => $rep->id]);
        $theirs = $this->shipmentFor($other, ['customer_name' => 'Somebody Elses']);

        $this->actingAs($rep)
            ->get(route('admin.shipments.index'))
            ->assertOk()
            ->assertSee($mine->tracking_number)
            ->assertSee($handed->tracking_number)
            ->assertDontSee($theirs->tracking_number);
    }

    public function test_a_representative_cannot_open_or_change_somebody_elses_shipment(): void
    {
        $rep = $this->representative();
        $theirs = $this->shipmentFor($this->representative());

        $this->actingAs($rep)->get(route('admin.shipments.show', $theirs))->assertForbidden();
        $this->actingAs($rep)->get(route('admin.shipments.edit', $theirs))->assertForbidden();
        $this->actingAs($rep)
            ->put(route('admin.shipments.update', $theirs), $this->validShipment())
            ->assertForbidden();
        $this->actingAs($rep)
            ->post(route('admin.shipments.events.store', $theirs), [
                'shipment_status_id' => $this->trackingStatus('delivered')->id,
                'occurred_at' => now()->toDateTimeString(),
            ])
            ->assertForbidden();
    }

    public function test_a_representative_can_update_the_tracking_they_handle(): void
    {
        $rep = $this->representative();
        $mine = $this->shipmentFor($rep);

        $this->actingAs($rep)
            ->post(route('admin.shipments.events.store', $mine), [
                'shipment_status_id' => $this->trackingStatus('arrived-at-port')->id,
                'occurred_at' => now()->toDateTimeString(),
                'is_public' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(1, $mine->fresh()->events()->count());
    }

    /**
     * The escalation that would make the whole allowance pointless: a
     * representative handing themselves somebody else's work, or handing
     * themselves work to dodge the count.
     */
    public function test_a_representative_cannot_assign_a_shipment_to_themselves(): void
    {
        $rep = $this->representative();
        $mine = $this->shipmentFor($rep);
        $theirs = $this->shipmentFor($this->representative());

        // On somebody else's shipment: refused outright.
        $this->actingAs($rep)
            ->put(route('admin.shipments.update', $theirs), $this->validShipment(['assigned_to' => $rep->id]))
            ->assertForbidden();

        $this->assertNull($theirs->fresh()->assigned_to);

        // On their own: allowed through, but the field is stripped before it
        // is read, so it changes nothing.
        $this->actingAs($rep)
            ->put(route('admin.shipments.update', $mine), $this->validShipment(['assigned_to' => $rep->id]))
            ->assertRedirect();

        $this->assertNull($mine->fresh()->assigned_to);
    }

    public function test_a_representative_cannot_raise_their_own_allowance(): void
    {
        $rep = $this->representative(['tracking_quota' => 5]);

        // The staff screens are shut to them entirely.
        $this->actingAs($rep)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($rep)->get(route('admin.users.edit', $rep))->assertForbidden();
        $this->actingAs($rep)
            ->put(route('admin.users.update', $rep), ['tracking_quota' => 9999])
            ->assertForbidden();

        // Nor through their own profile, which has no such field.
        $this->actingAs($rep)->put(route('admin.profile.update'), [
            'name' => $rep->name,
            'email' => $rep->email,
            'tracking_quota' => 9999,
        ]);

        $this->assertSame(5, $rep->fresh()->tracking_quota);
    }

    public function test_a_representative_still_cannot_archive_or_reach_the_rest_of_the_panel(): void
    {
        $rep = $this->representative();
        $mine = $this->shipmentFor($rep);

        $this->actingAs($rep)->post(route('admin.shipments.archive', $mine))->assertForbidden();

        foreach ([
            'admin.customers.index',
            'admin.settings.edit',
            'admin.audit-logs.index',
            'admin.quotes.index',
            'admin.services.index',
        ] as $route) {
            $this->actingAs($rep)->get(route($route))->assertForbidden();
        }
    }

    public function test_a_suspended_representative_loses_the_allowance_with_everything_else(): void
    {
        $rep = User::factory()->representative()->suspended()->create(['tracking_quota' => 5]);

        $this->assertFalse($rep->canRaiseTracking());

        $this->actingAs($rep)
            ->post(route('admin.shipments.store'), $this->validShipment())
            ->assertRedirect(route('admin.suspended'));

        $this->assertSame(0, Shipment::count());
    }

    /**
     * A regression, and an easy one to reintroduce. Giving representatives
     * shipments.view once flipped them onto the master admin dashboard, which
     * counts every shipment on the system and lists the quote requests they
     * are not allowed to open.
     */
    public function test_a_representative_stays_on_their_own_dashboard(): void
    {
        $rep = $this->representative();
        $somebodyElses = $this->shipmentFor($this->representative());

        $response = $this->actingAs($rep)->get(route('admin.dashboard'))->assertOk();

        $response->assertSee('My conversations');
        $response->assertDontSee('Recent quote requests');
        $response->assertDontSee('Total shipments');
        $response->assertDontSee($somebodyElses->tracking_number);
    }

    public function test_archived_shipments_are_counted_against_the_allowance(): void
    {
        $rep = $this->representative(['tracking_quota' => 2]);

        $this->shipmentFor($rep, ['archived_at' => now()]);
        $this->shipmentFor($rep);

        // Archiving does not take the tracking number back off the customer
        // holding it, so it still counts.
        $this->assertSame(2, $rep->fresh()->trackingUsed());
        $this->assertFalse($rep->fresh()->canRaiseTracking());
    }
}

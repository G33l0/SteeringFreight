<?php

namespace Tests\Feature\Admin;

use App\Enums\ShippingMethod;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Notifications\ShipmentStatusUpdated;
use App\Services\TrackingNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ShipmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    public function test_an_administrator_can_create_a_shipment(): void
    {
        $user = $this->administrator();
        $status = $this->trackingStatus('booking-confirmed');

        $response = $this->actingAs($user)->post(route('admin.shipments.store'), [
            'customer_name' => 'Rivera Imports',
            'customer_email' => 'ops@example.com',
            'origin_city' => 'Ningbo',
            'origin_country' => 'China',
            'destination_city' => 'Lagos',
            'destination_country' => 'Nigeria',
            'shipping_method' => ShippingMethod::SeaFreightLcl->value,
            'shipment_status_id' => $status->id,
            'estimated_delivery' => now()->addDays(20)->toDateString(),
        ]);

        $shipment = Shipment::firstOrFail();

        $response->assertRedirect(route('admin.shipments.show', $shipment));
        $this->assertSame('Rivera Imports', $shipment->customer_name);
        $this->assertSame($user->id, $shipment->created_by);

        // A tracking number is issued automatically using the configured prefix.
        $this->assertStringStartsWith(app(TrackingNumberGenerator::class)->prefix().'-', $shipment->tracking_number);

        // The opening status is recorded as the first tracking event.
        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'shipment_status_id' => $status->id,
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'shipment.created', 'user_id' => $user->id]);
    }

    public function test_generated_tracking_numbers_are_unique(): void
    {
        $generator = app(TrackingNumberGenerator::class);

        $numbers = collect(range(1, 25))->map(fn () => tap($generator->generate(), function (string $number): void {
            Shipment::factory()->create(['tracking_number' => $number]);
        }));

        $this->assertSame($numbers->count(), $numbers->unique()->count());
        $this->assertSame(25, Shipment::count());
    }

    public function test_a_duplicate_tracking_number_is_rejected(): void
    {
        $existing = Shipment::factory()->create();

        $this->actingAs($this->administrator())
            ->post(route('admin.shipments.store'), [
                'tracking_number' => $existing->tracking_number,
                'customer_name' => 'Duplicate',
            ])
            ->assertSessionHasErrors('tracking_number');

        $this->assertSame(1, Shipment::count());
    }

    public function test_adding_a_tracking_event_moves_the_shipment_forward(): void
    {
        Notification::fake();

        $user = $this->administrator();
        $shipment = Shipment::factory()->create(['shipment_status_id' => $this->trackingStatus('booking-confirmed')->id]);
        $inTransit = $this->trackingStatus('in-transit');

        $this->actingAs($user)
            ->post(route('admin.shipments.events.store', $shipment), [
                'shipment_status_id' => $inTransit->id,
                'location' => 'Atlantic Ocean',
                'occurred_at' => now()->toDateTimeString(),
                'description' => 'Shipment is currently in transit to the destination port.',
                'internal_note' => 'Checked against the carrier schedule.',
                'is_public' => '1',
                'update_shipment' => '1',
            ])
            ->assertRedirect(route('admin.shipments.show', $shipment));

        $shipment->refresh();

        $this->assertSame($inTransit->id, $shipment->shipment_status_id);
        $this->assertSame('Atlantic Ocean', $shipment->current_location);
        $this->assertSame((int) $inTransit->stage, $shipment->progress_stage);
        $this->assertDatabaseHas('audit_logs', ['action' => 'shipment.event_created']);
    }

    public function test_the_customer_is_emailed_when_notifications_are_enabled(): void
    {
        Notification::fake();
        settings()->set('notifications.enabled', true);

        $shipment = Shipment::factory()->create(['customer_email' => 'customer@example.com']);
        $shipped = $this->trackingStatus('shipped');
        $this->assertTrue($shipped->notify_customer);

        $this->actingAs($this->administrator())->post(route('admin.shipments.events.store', $shipment), [
            'shipment_status_id' => $shipped->id,
            'occurred_at' => now()->toDateTimeString(),
            'description' => 'The consignment has left the origin facility.',
            'is_public' => '1',
            'update_shipment' => '1',
            'notify_customer' => '1',
        ])->assertRedirect();

        Notification::assertSentOnDemand(ShipmentStatusUpdated::class);
        $this->assertTrue($shipment->events()->first()->notified_customer);
    }

    public function test_no_email_is_sent_when_notifications_are_switched_off(): void
    {
        Notification::fake();
        settings()->set('notifications.enabled', false);

        $shipment = Shipment::factory()->create(['customer_email' => 'customer@example.com']);

        $this->actingAs($this->administrator())->post(route('admin.shipments.events.store', $shipment), [
            'shipment_status_id' => $this->trackingStatus('shipped')->id,
            'occurred_at' => now()->toDateTimeString(),
            'description' => 'The consignment has left the origin facility.',
            'is_public' => '1',
            'update_shipment' => '1',
            'notify_customer' => '1',
        ])->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_an_exception_status_requires_an_explanation(): void
    {
        $shipment = Shipment::factory()->create();

        $this->actingAs($this->administrator())
            ->post(route('admin.shipments.events.store', $shipment), [
                'shipment_status_id' => $this->trackingStatus('customs-hold')->id,
                'occurred_at' => now()->toDateTimeString(),
                'description' => '',
            ])
            ->assertSessionHasErrors('description');

        $this->assertSame(0, ShipmentEvent::count());
    }

    public function test_setting_an_exception_status_on_the_shipment_needs_an_explanation(): void
    {
        $shipment = Shipment::factory()->create();

        $this->actingAs($this->administrator())
            ->put(route('admin.shipments.update', $shipment), [
                'shipment_status_id' => $this->trackingStatus('customs-hold')->id,
                'exception_note' => '',
            ])
            ->assertSessionHasErrors('exception_note');

        $this->actingAs($this->administrator())
            ->put(route('admin.shipments.update', $shipment), [
                'shipment_status_id' => $this->trackingStatus('customs-hold')->id,
                'exception_note' => 'Customs has asked for the original certificate of origin.',
            ])
            ->assertRedirect();

        $this->assertSame('Customs has asked for the original certificate of origin.', $shipment->fresh()->exception_note);
    }

    public function test_an_edit_never_clears_the_tracking_number(): void
    {
        $shipment = Shipment::factory()->create();
        $original = $shipment->tracking_number;

        $this->actingAs($this->administrator())
            ->put(route('admin.shipments.update', $shipment), ['customer_name' => 'Renamed Client'])
            ->assertRedirect();

        $shipment->refresh();

        $this->assertSame($original, $shipment->tracking_number);
        $this->assertSame('Renamed Client', $shipment->customer_name);
    }

    public function test_events_can_be_edited_and_deleted(): void
    {
        $user = $this->administrator();
        $shipment = Shipment::factory()->create();
        $event = ShipmentEvent::factory()->create([
            'shipment_id' => $shipment->id,
            'shipment_status_id' => $this->trackingStatus('shipped')->id,
        ]);

        $this->actingAs($user)->put(route('admin.shipments.events.update', [$shipment, $event]), [
            'shipment_status_id' => $this->trackingStatus('in-transit')->id,
            'location' => 'Suez Canal',
            'occurred_at' => now()->toDateTimeString(),
            'description' => 'Updated wording.',
            'is_public' => '1',
        ])->assertRedirect();

        $this->assertSame('Suez Canal', $event->fresh()->location);

        $this->actingAs($user)
            ->delete(route('admin.shipments.events.destroy', [$shipment, $event]))
            ->assertRedirect();

        $this->assertSame(0, ShipmentEvent::count());
    }

    public function test_shipments_can_be_archived_and_restored(): void
    {
        $user = $this->administrator();
        $shipment = Shipment::factory()->create();

        $this->actingAs($user)->post(route('admin.shipments.archive', $shipment))->assertRedirect();
        $this->assertNotNull($shipment->fresh()->archived_at);

        $this->actingAs($user)->post(route('admin.shipments.restore', $shipment))->assertRedirect();
        $this->assertNull($shipment->fresh()->archived_at);
    }

    public function test_the_shipment_list_can_be_searched_and_filtered(): void
    {
        $user = $this->administrator();
        $matching = Shipment::factory()->create(['customer_name' => 'Harbour Foods', 'destination_city' => 'Tema']);
        $other = Shipment::factory()->create(['customer_name' => 'Unrelated Trading', 'destination_city' => 'Hamburg']);

        $this->actingAs($user)
            ->get(route('admin.shipments.index', ['q' => 'Harbour']))
            ->assertOk()
            ->assertSee($matching->tracking_number)
            ->assertDontSee($other->tracking_number);
    }
}

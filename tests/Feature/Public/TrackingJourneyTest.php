<?php

namespace Tests\Feature\Public;

use App\Models\Shipment;
use App\Models\ShipmentStatus;
use App\Support\TrackingIcons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the tracking page shows a customer.
 *
 * The journey bar answers "where is my cargo" before a word is read. The stage
 * checklist and the raw percentage are operational detail: a checklist invites
 * questions about stages that have not happened, and a percentage reads as a
 * promise about time that freight cannot keep. Both are off unless the business
 * decides otherwise.
 */
class TrackingJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    private function shipment(array $attributes = []): Shipment
    {
        // Attributes first: PHP's array union keeps the left-hand side for a
        // duplicate key, so defaults must be on the right or they win.
        return Shipment::factory()->create($attributes + [
            'shipment_status_id' => $this->trackingStatus('in-transit')->id,
        ]);
    }

    public function test_the_stages_reached_are_shown_to_customers(): void
    {
        $shipment = $this->shipment([
            'shipment_status_id' => $this->trackingStatus('processing')->id,
            'progress_stage' => 3,
        ]);

        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertSee('Shipment stages')
            ->assertSee('Booking Confirmed')
            ->assertSee('Cargo Received')
            ->assertSee('Processing');
    }

    /**
     * The point of the whole thing. A customer shown eleven greyed out
     * milestones is being shown a plan rather than a shipment, and every one of
     * them invites a question nobody can answer yet — "why is customs clearance
     * not done" about a box still on the water.
     */
    public function test_stages_that_have_not_happened_yet_are_left_out(): void
    {
        $shipment = $this->shipment([
            'shipment_status_id' => $this->trackingStatus('processing')->id,
            'progress_stage' => 3,
        ]);

        $response = $this->get(route('track.show', $shipment->tracking_number))->assertOk();

        foreach (['Packing', 'Ready for Dispatch', 'Shipped', 'In Transit', 'Arrived at Port', 'Customs Clearance', 'Out for Delivery'] as $future) {
            $response->assertDontSee($future);
        }
    }

    /**
     * The destination stays visible from the first day, so the customer can see
     * where this ends — just not ticked until it has actually happened.
     */
    public function test_the_final_stage_is_visible_from_the_start_but_not_marked(): void
    {
        $shipment = $this->shipment([
            'shipment_status_id' => $this->trackingStatus('processing')->id,
            'progress_stage' => 3,
        ]);

        $visible = ShipmentStatus::customerTimelineFor($shipment);

        $this->assertSame('Delivered', $visible->last()->name);
        $this->assertSame(
            ['Booking Confirmed', 'Cargo Received', 'Processing', 'Delivered'],
            $visible->pluck('name')->all(),
        );

        // Present on the page, but behind the reached stages rather than among them.
        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertSee('Delivered');
    }

    public function test_a_delivered_shipment_does_not_list_the_destination_twice(): void
    {
        $shipment = $this->shipment([
            'shipment_status_id' => $this->trackingStatus('delivered')->id,
            'progress_stage' => 14,
        ]);

        $names = ShipmentStatus::customerTimelineFor($shipment)->pluck('name');

        $this->assertSame(1, $names->filter(fn (string $n) => $n === 'Delivered')->count());
        $this->assertSame('Delivered', $names->last());
    }

    public function test_a_brand_new_shipment_shows_its_destination_and_little_else(): void
    {
        $shipment = $this->shipment([
            'shipment_status_id' => $this->trackingStatus('booking-confirmed')->id,
            'progress_stage' => 1,
        ]);

        $this->assertSame(
            ['Booking Confirmed', 'Delivered'],
            ShipmentStatus::customerTimelineFor($shipment)->pluck('name')->all(),
        );
    }

    public function test_the_stage_list_can_still_be_turned_off(): void
    {
        $this->setSetting('tracking.show_stages', false);

        $shipment = $this->shipment(['progress_stage' => 3]);

        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertDontSee('Shipment stages');
    }

    public function test_the_percentage_is_hidden_from_customers_by_default(): void
    {
        $shipment = $this->shipment();

        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertDontSee($shipment->progressPercent().'%');
    }

    public function test_a_business_can_turn_the_percentage_back_on(): void
    {
        $this->setSetting('tracking.show_percentage', true);

        $shipment = $this->shipment();

        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertSee($shipment->progressPercent().'%');
    }

    public function test_the_journey_bar_is_always_there(): void
    {
        $shipment = $this->shipment();

        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertSee('Shipment progress:', false)
            ->assertSee('role="progressbar"', false);
    }

    public function test_sea_freight_is_drawn_as_a_ship_and_air_as_an_aircraft(): void
    {
        $sea = $this->shipment(['shipping_method' => 'sea_fcl', 'origin_country' => 'Nigeria', 'destination_country' => 'Netherlands']);
        $air = $this->shipment(['shipping_method' => 'air', 'origin_country' => 'Nigeria', 'destination_country' => 'Netherlands']);

        $this->assertSame('vessel', TrackingIcons::vehicleFor($sea));
        $this->assertSame('aircraft', TrackingIcons::vehicleFor($air));
    }

    /**
     * Both ends in one country is a van turning up at a door, whatever the
     * booking called it — that is the leg the customer can picture.
     */
    public function test_a_domestic_shipment_is_drawn_as_a_van_whatever_the_booking_says(): void
    {
        $domestic = $this->shipment([
            'shipping_method' => 'sea_fcl',
            'origin_country' => 'Nigeria',
            'destination_country' => 'Nigeria',
        ]);

        $this->assertTrue(TrackingIcons::isDomestic($domestic));
        $this->assertSame('truck', TrackingIcons::vehicleFor($domestic));
    }

    public function test_each_exception_carries_an_icon_that_means_something(): void
    {
        $cases = [
            'customs-hold' => 'customs',
            'customs-inspection' => 'customs',
            'delayed' => 'clock',
            'weather-delay' => 'clock',
            'port-congestion' => 'clock',
            'documentation-required' => 'documents',
            'missing-documentation' => 'documents',
            'cargo-verification' => 'search',
            'damaged-cargo' => 'alert',
            'delivery-attempted' => 'truck',
        ];

        foreach ($cases as $slug => $expected) {
            $this->assertSame($expected, TrackingIcons::forStatus($this->trackingStatus($slug)), "wrong icon for {$slug}");
        }
    }

    public function test_a_delivered_shipment_arrives_at_a_door(): void
    {
        $this->assertSame('home', TrackingIcons::forStatus($this->trackingStatus('delivered')));
    }

    public function test_an_exception_is_explained_on_the_page(): void
    {
        $shipment = $this->shipment([
            'shipment_status_id' => $this->trackingStatus('customs-hold')->id,
            'exception_note' => 'Customs have asked for the original invoice.',
        ]);

        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertSee('Customs have asked for the original invoice.');
    }

    /** The public site draws no emoji, flags beside a country name excepted. */
    public function test_the_journey_bar_uses_drawings_not_emoji(): void
    {
        $shipment = $this->shipment();

        $body = $this->get(route('track.show', $shipment->tracking_number))->assertOk()->getContent();

        foreach (['🚢', '✈️', '✈', '🚚', '🚐', '⚠️', '📦', '🏠'] as $emoji) {
            $this->assertStringNotContainsString($emoji, $body, "found {$emoji} on the tracking page");
        }
    }
}

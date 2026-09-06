<?php

namespace Tests\Feature\Public;

use App\Models\Shipment;
use App\Models\ShipmentEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    public function test_the_tracking_form_is_reachable(): void
    {
        $this->get(route('track.index'))
            ->assertOk()
            ->assertSee('Track your shipment');
    }

    public function test_a_shipment_can_be_looked_up_by_tracking_number(): void
    {
        $shipment = Shipment::factory()->create([
            'shipment_status_id' => $this->trackingStatus('in-transit')->id,
            'current_location' => 'Atlantic Ocean',
            'progress_stage' => 7,
        ]);

        $this->post(route('track.lookup'), ['tracking_number' => $shipment->tracking_number])
            ->assertRedirect(route('track.show', $shipment->tracking_number));

        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertSee($shipment->tracking_number)
            ->assertSee('Atlantic Ocean')
            ->assertSee('In Transit');
    }

    public function test_tracking_numbers_are_normalised_before_lookup(): void
    {
        $shipment = Shipment::factory()->create();
        $messy = strtolower(str_replace('-', ' ', $shipment->tracking_number));

        $this->post(route('track.lookup'), ['tracking_number' => $messy])
            ->assertRedirect(route('track.show', $shipment->tracking_number));
    }

    public function test_an_unknown_tracking_number_returns_a_helpful_message(): void
    {
        $this->from(route('track.index'))
            ->get(route('track.show', 'PLS-00000000'))
            ->assertRedirect(route('track.index'))
            ->assertSessionHasErrors('tracking_number');

        $this->followingRedirects()
            ->get(route('track.show', 'PLS-00000000'))
            ->assertSee("We couldn't find a shipment with that tracking number.");
    }

    public function test_internal_notes_and_private_events_are_never_shown_to_customers(): void
    {
        $shipment = Shipment::factory()->create(['internal_notes' => 'Margin is thin on this booking.']);

        ShipmentEvent::factory()->create([
            'shipment_id' => $shipment->id,
            'shipment_status_id' => $this->trackingStatus('in-transit')->id,
            'description' => 'Shipment is currently in transit to the destination port.',
            'internal_note' => 'Carrier contact says the vessel may be rolled.',
            'is_public' => true,
        ]);

        ShipmentEvent::factory()->internal()->create([
            'shipment_id' => $shipment->id,
            'shipment_status_id' => $this->trackingStatus('processing')->id,
            'description' => 'Awaiting supplier confirmation before booking.',
        ]);

        $response = $this->get(route('track.show', $shipment->tracking_number));

        $response->assertOk()
            ->assertSee('Shipment is currently in transit to the destination port.')
            ->assertDontSee('Margin is thin on this booking.')
            ->assertDontSee('Carrier contact says the vessel may be rolled.')
            ->assertDontSee('Awaiting supplier confirmation before booking.');
    }

    public function test_the_tracking_page_is_not_indexed(): void
    {
        $shipment = Shipment::factory()->create();

        $this->get(route('track.show', $shipment->tracking_number))
            ->assertSee('noindex, nofollow', false);
    }

    public function test_tracking_lookups_are_rate_limited(): void
    {
        $shipment = Shipment::factory()->create();

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->post(route('track.lookup'), ['tracking_number' => $shipment->tracking_number]);
        }

        $this->post(route('track.lookup'), ['tracking_number' => $shipment->tracking_number])
            ->assertStatus(429);
    }
}

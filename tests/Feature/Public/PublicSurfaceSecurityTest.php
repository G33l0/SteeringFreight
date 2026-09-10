<?php

namespace Tests\Feature\Public;

use App\Models\Shipment;
use App\Models\ShipmentDocument;
use App\Models\ShipmentEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * What somebody with no account, and at most a tracking number, can reach.
 */
class PublicSurfaceSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        Notification::fake();
    }

    public function test_a_tracking_number_does_not_reveal_who_the_customer_is(): void
    {
        $shipment = Shipment::factory()->create([
            'customer_name' => 'Adaeze Nwosu',
            'customer_email' => 'adaeze@example.test',
            'customer_phone' => '+234 800 555 0000',
            'internal_notes' => 'Customer disputes the demurrage invoice.',
        ]);

        $response = $this->get(route('track.show', $shipment->tracking_number))->assertOk();

        $response->assertDontSee('Adaeze Nwosu');
        $response->assertDontSee('adaeze@example.test');
        $response->assertDontSee('+234 800 555 0000');
        $response->assertDontSee('demurrage');
    }

    public function test_internal_tracking_events_never_appear_on_the_tracking_page(): void
    {
        $shipment = Shipment::factory()->create();

        ShipmentEvent::factory()->create([
            'shipment_id' => $shipment->id,
            'is_public' => false,
            'description' => 'Held pending payment of our own invoice.',
        ]);
        ShipmentEvent::factory()->create([
            'shipment_id' => $shipment->id,
            'is_public' => true,
            'description' => 'Arrived at the destination port.',
        ]);

        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertSee('Arrived at the destination port.')
            ->assertDontSee('Held pending payment');
    }

    public function test_an_internal_document_cannot_be_downloaded_by_a_visitor(): void
    {
        Storage::fake('local');

        $shipment = Shipment::factory()->create();
        $admin = $this->administrator();

        $internal = ShipmentDocument::factory()->create([
            'shipment_id' => $shipment->id,
            'visibility' => 'internal',
            'path' => 'shipments/internal.pdf',
            'uploaded_by' => $admin->id,
        ]);
        Storage::disk('local')->put('shipments/internal.pdf', 'our margin on this job');

        // Even after looking the shipment up, which is what unlocks customer files.
        $this->get(route('track.show', $shipment->tracking_number))->assertOk();

        $this->get(route('track.documents.download', [
            'tracking_number' => $shipment->tracking_number,
            'document' => $internal,
        ]))->assertNotFound();
    }

    public function test_a_customer_document_needs_the_shipment_to_have_been_looked_up(): void
    {
        Storage::fake('local');

        $shipment = Shipment::factory()->create();
        $document = ShipmentDocument::factory()->create([
            'shipment_id' => $shipment->id,
            'visibility' => 'customer',
            'path' => 'shipments/bill-of-lading.pdf',
            'uploaded_by' => $this->administrator()->id,
        ]);
        Storage::disk('local')->put('shipments/bill-of-lading.pdf', 'bill of lading');

        $url = route('track.documents.download', [
            'tracking_number' => $shipment->tracking_number,
            'document' => $document,
        ]);

        // Straight to the file, without ever visiting the tracking page.
        $this->get($url)->assertForbidden();

        $this->get(route('track.show', $shipment->tracking_number))->assertOk();
        $this->get($url)->assertOk();
    }

    public function test_a_document_id_from_another_shipment_is_not_served(): void
    {
        Storage::fake('local');

        $mine = Shipment::factory()->create();
        $theirs = Shipment::factory()->create();

        $document = ShipmentDocument::factory()->create([
            'shipment_id' => $theirs->id,
            'visibility' => 'customer',
            'path' => 'shipments/theirs.pdf',
            'uploaded_by' => $this->administrator()->id,
        ]);
        Storage::disk('local')->put('shipments/theirs.pdf', 'somebody else paperwork');

        $this->get(route('track.show', $mine->tracking_number))->assertOk();

        $this->get(route('track.documents.download', [
            'tracking_number' => $mine->tracking_number,
            'document' => $document,
        ]))->assertNotFound();
    }

    public function test_administrator_written_copy_cannot_inject_scripts(): void
    {
        settings()->set('company.intro', '<script>alert(1)</script> **Genuine** copy <img src=x onerror=alert(1)>');

        $response = $this->get(route('about'))->assertOk();

        // What matters is that none of it reaches the browser as markup. The
        // page has script tags of its own, so this checks for the injected ones.
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertDontSee('<img src=x onerror', false);

        // It arrives escaped, and reads as the text somebody typed.
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);

        // The safe subset still works.
        $response->assertSee('<strong>Genuine</strong>', false);
    }

    public function test_the_admin_panel_is_closed_to_visitors(): void
    {
        foreach ([
            route('admin.dashboard'),
            route('admin.shipments.index'),
            route('admin.users.index'),
            route('admin.settings.edit'),
            route('admin.audit-logs.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
        }
    }

    public function test_tracking_lookups_are_rate_limited(): void
    {
        $shipment = Shipment::factory()->create();

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->get(route('track.show', $shipment->tracking_number));
        }

        // Guessing tracking numbers in bulk runs into the limiter.
        $this->get(route('track.show', $shipment->tracking_number))->assertStatus(429);
    }

    public function test_the_quote_form_is_rate_limited(): void
    {
        $payload = [
            'name' => 'Bulk Sender',
            'email' => 'bulk@example.test',
            'origin_country' => 'China',
            'destination_country' => 'Nigeria',
            'cargo_type' => 'Assorted',
        ];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('quote.store'), $payload);
        }

        $this->post(route('quote.store'), $payload)->assertStatus(429);
    }

    public function test_a_bot_filling_the_hidden_field_is_turned_away(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Spam Bot',
            'email' => 'bot@example.test',
            'subject' => 'Cheap watches',
            'message' => 'Buy our products at a discount today.',
            'website' => 'https://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertDatabaseMissing('contact_messages', ['email' => 'bot@example.test']);
    }
}

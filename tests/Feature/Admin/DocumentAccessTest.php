<?php

namespace Tests\Feature\Admin;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Shipment;
use App\Models\ShipmentDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        Storage::fake('local');
    }

    public function test_an_administrator_can_upload_a_document(): void
    {
        $user = $this->administrator();
        $shipment = Shipment::factory()->create();

        $this->actingAs($user)->post(route('admin.shipments.documents.store', $shipment), [
            'title' => 'Commercial invoice',
            'type' => DocumentType::CommercialInvoice->value,
            'visibility' => DocumentVisibility::Customer->value,
            'file' => UploadedFile::fake()->create('invoice.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('admin.shipments.show', $shipment));

        $document = ShipmentDocument::firstOrFail();

        Storage::disk('local')->assertExists($document->path);
        $this->assertStringContainsString('shipments/'.$shipment->id.'/documents', $document->path);
        $this->assertSame('invoice.pdf', $document->original_name);

        // The stored name is generated, so nothing can be guessed from the URL.
        $this->assertStringNotContainsString('invoice', basename($document->path));
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.uploaded']);
    }

    public function test_dangerous_file_types_are_rejected(): void
    {
        $shipment = Shipment::factory()->create();

        $this->actingAs($this->administrator())
            ->post(route('admin.shipments.documents.store', $shipment), [
                'title' => 'Not a document',
                'type' => DocumentType::Other->value,
                'visibility' => DocumentVisibility::Internal->value,
                'file' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, ShipmentDocument::count());
    }

    public function test_a_customer_visible_document_needs_a_tracking_session(): void
    {
        $shipment = Shipment::factory()->create();
        $document = ShipmentDocument::factory()->customerVisible()->create(['shipment_id' => $shipment->id]);
        Storage::disk('local')->put($document->path, 'file contents');

        $url = route('track.documents.download', [
            'tracking_number' => $shipment->tracking_number,
            'document' => $document,
        ]);

        // Without looking the shipment up first, the download is refused.
        $this->get($url)->assertForbidden();

        $this->get(route('track.show', $shipment->tracking_number))->assertOk();
        $this->get($url)->assertOk();
    }

    public function test_internal_documents_are_never_downloadable_from_the_tracking_page(): void
    {
        $shipment = Shipment::factory()->create();
        $document = ShipmentDocument::factory()->create(['shipment_id' => $shipment->id]);
        Storage::disk('local')->put($document->path, 'internal contents');

        $this->get(route('track.show', $shipment->tracking_number))->assertOk();

        $this->get(route('track.documents.download', [
            'tracking_number' => $shipment->tracking_number,
            'document' => $document,
        ]))->assertNotFound();
    }

    public function test_internal_documents_are_not_listed_on_the_tracking_page(): void
    {
        $shipment = Shipment::factory()->create();
        ShipmentDocument::factory()->create(['shipment_id' => $shipment->id, 'title' => 'Internal costing sheet']);
        ShipmentDocument::factory()->customerVisible()->create(['shipment_id' => $shipment->id, 'title' => 'Bill of lading copy']);

        $this->get(route('track.show', $shipment->tracking_number))
            ->assertSee('Bill of lading copy')
            ->assertDontSee('Internal costing sheet');
    }

    public function test_a_document_cannot_be_downloaded_through_another_shipment(): void
    {
        $shipment = Shipment::factory()->create();
        $other = Shipment::factory()->create();
        $document = ShipmentDocument::factory()->customerVisible()->create(['shipment_id' => $other->id]);
        Storage::disk('local')->put($document->path, 'file contents');

        $this->get(route('track.show', $shipment->tracking_number))->assertOk();

        $this->get(route('track.documents.download', [
            'tracking_number' => $shipment->tracking_number,
            'document' => $document,
        ]))->assertNotFound();
    }

    public function test_guests_cannot_download_documents_through_the_admin_route(): void
    {
        $shipment = Shipment::factory()->create();
        $document = ShipmentDocument::factory()->create(['shipment_id' => $shipment->id]);

        $this->get(route('admin.shipments.documents.download', [$shipment, $document]))
            ->assertRedirect(route('admin.login'));
    }
}

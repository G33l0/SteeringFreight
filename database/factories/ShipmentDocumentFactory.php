<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Shipment;
use App\Models\ShipmentDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentDocument>
 */
class ShipmentDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'title' => 'Commercial invoice',
            'type' => DocumentType::CommercialInvoice,
            'original_name' => 'invoice.pdf',
            'path' => 'shipments/test/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
            'visibility' => DocumentVisibility::Internal,
        ];
    }

    public function customerVisible(): static
    {
        return $this->state(fn () => ['visibility' => DocumentVisibility::Customer]);
    }
}

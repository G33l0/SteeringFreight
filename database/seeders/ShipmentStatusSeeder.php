<?php

namespace Database\Seeders;

use App\Enums\StatusCategory;
use App\Models\ShipmentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The default tracking timeline and the exception statuses. Everything here can
 * be renamed, reordered, switched off or extended from the admin panel.
 */
class ShipmentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $milestones = [
            ['Booking Confirmed', 'The booking has been confirmed and a tracking number issued.', 'slate', false],
            ['Cargo Received', 'The cargo has been received at our origin facility.', 'slate', true],
            ['Processing', 'Documentation is being prepared and the cargo checked against the packing list.', 'slate', false],
            ['Packing', 'The cargo is being packed and secured for transport.', 'slate', false],
            ['Ready for Dispatch', 'The consignment is packed and waiting for its scheduled departure.', 'blue', false],
            ['Shipped', 'The consignment has left the origin facility.', 'blue', true],
            ['In Transit', 'The shipment is currently in transit to the destination port.', 'blue', false],
            ['Arrived at Port', 'The vessel or aircraft has arrived at the destination.', 'blue', true],
            ['Customs Clearance', 'The consignment has been presented to customs at destination.', 'teal', false],
            ['Clearance Completed', 'Customs formalities at destination have been completed.', 'teal', true],
            ['Released', 'The consignment has been released for onward movement.', 'teal', false],
            ['Destination Warehouse', 'The cargo is at our destination warehouse awaiting delivery.', 'teal', false],
            ['Out for Delivery', 'The consignment is on the vehicle for final delivery.', 'green', true],
            ['Delivered', 'The consignment has been delivered.', 'green', true],
        ];

        foreach ($milestones as $index => [$name, $description, $colour, $notify]) {
            ShipmentStatus::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'category' => StatusCategory::Milestone,
                    'stage' => $index + 1,
                    'sort_order' => $index + 1,
                    'colour' => $colour,
                    'description' => $description,
                    'is_active' => true,
                    'is_final' => $name === 'Delivered',
                    'notify_customer' => $notify,
                    'requires_explanation' => false,
                ],
            );
        }

        $exceptions = [
            ['Delayed', 'The shipment is running behind the planned schedule.', 'amber'],
            ['Weather Delay', 'Movement has been held up by weather conditions.', 'amber'],
            ['Port Congestion', 'Operations at the port are congested and berthing or release is taking longer than planned.', 'amber'],
            ['Customs Inspection', 'The consignment has been selected for examination by customs.', 'amber'],
            ['Customs Hold', 'Customs has placed the consignment on hold.', 'red'],
            ['Documentation Required', 'Further documentation is needed before the consignment can move on.', 'amber'],
            ['Cargo Verification', 'The cargo is being verified against the shipping documents.', 'amber'],
            ['Delivery Attempted', 'A delivery was attempted but could not be completed.', 'amber'],
            ['Delivery Rescheduled', 'Delivery has been rescheduled.', 'amber'],
            ['Damaged Cargo', 'Damage has been reported and is being assessed.', 'red'],
            ['Missing Documentation', 'A required document is missing from the file.', 'red'],
            ['Other Issue', 'An exception has been recorded against this shipment.', 'red'],
        ];

        foreach ($exceptions as $index => [$name, $description, $colour]) {
            ShipmentStatus::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'category' => StatusCategory::Exception,
                    'stage' => null,
                    'sort_order' => 100 + $index,
                    'colour' => $colour,
                    'description' => $description,
                    'is_active' => true,
                    'is_final' => false,
                    'notify_customer' => in_array($name, ['Delayed', 'Customs Hold', 'Delivery Attempted'], true),
                    // Every exception needs a written explanation from a member
                    // of staff before it can be shown to the customer.
                    'requires_explanation' => true,
                ],
            );
        }

        ShipmentStatus::forgetCachedTimeline();
    }
}

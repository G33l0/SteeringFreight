<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'title' => 'Sea Freight',
                'slug' => 'sea-freight',
                'icon' => 'vessel',
                'summary' => 'Full container and groupage bookings, arranged with the carrier, packed for the voyage and cleared at both ends.',
                'highlights' => ['FCL in 20ft, 40ft and 40ft high cube', 'LCL groupage where a full container is not needed', 'Port to port or door to door', 'Booking, bill of lading and release handled by one desk'],
                'description' => <<<'TEXT'
Sea freight is the workhorse of most import and export programmes: slower than air, but the right choice for volume, weight and anything where the schedule allows a few weeks in transit.

## Full container load

We confirm space with the carrier before we confirm your rate, so the sailing you are quoted is the sailing you are booked on. Containers can be stuffed at your own premises or at our warehouse, and we arrange haulage at both ends.

## Groupage and part loads

If your cargo does not fill a container, it travels in a groupage container with other consignments going the same way. You pay for the space you use, calculated on whichever is greater, volume or weight.

## What we handle

- Carrier booking and space confirmation
- Collection from the supplier and delivery to the port of loading
- Container stuffing, lashing and securing
- Bill of lading, certificates of origin and export declarations
- Destination clearance, port charges and final delivery

## Transit times

Transit time depends on the route, the carrier's rotation and whether the service is direct or routed through a transhipment hub. We quote the realistic door to door time rather than just the port to port sailing time, and we tell you when a vessel is rolled.
TEXT,
            ],
            [
                'title' => 'Air Freight',
                'slug' => 'air-freight',
                'icon' => 'aircraft',
                'summary' => 'Airport to airport and door to door air cargo, for consignments where the delivery date matters more than the freight cost.',
                'highlights' => ['Consolidated and direct air waybills', 'Next flight out for urgent consignments', 'Temperature and time sensitive cargo', 'Airport handling and customs at both ends'],
                'description' => <<<'TEXT'
Air freight is the option when a production line is waiting, when a shipment has missed its vessel, or when the cargo is too valuable or too perishable to spend weeks at sea.

## How it works

We book capacity with the airline, prepare the air waybill and arrange the trucking to and from the airport. Cargo is screened in line with aviation security requirements before it is accepted, so the paperwork has to be right before the consignment leaves you.

## What we handle

- Airline booking and air waybill issue
- Pickup, palletising and screening
- Dangerous goods documentation where applicable
- Airport handling, customs clearance and delivery

## Costing

Air freight is charged on chargeable weight, which is the greater of the gross weight and the volumetric weight. Cartons that are light but bulky often cost more than the scale suggests, so send us dimensions with the weight and we will tell you which applies.
TEXT,
            ],
            [
                'title' => 'Customs Clearance',
                'slug' => 'customs-clearance',
                'icon' => 'customs',
                'summary' => 'Import and export declarations prepared and lodged for you, with duty and tax worked out before the cargo arrives.',
                'highlights' => ['Import and export declarations', 'Tariff classification and valuation', 'Duty and tax estimates before arrival', 'Support during inspections and queries'],
                'description' => <<<'TEXT'
Most delays at the border are document problems, not cargo problems. We prepare declarations from the commercial documents you send us and raise questions early, while there is still time to fix them.

## Before arrival

We check the commercial invoice, packing list and transport document against each other, classify the goods and calculate the expected duty and tax. If a licence, permit or certificate is required, you hear about it before the shipment sails, not after it lands.

## On arrival

The declaration is lodged as soon as the manifest is available. If the consignment is selected for examination we attend, keep you informed and record what happens on your tracking page.

## What we need from you

- Commercial invoice showing value, currency and terms of sale
- Packing list with weights and package counts
- Transport document, bill of lading or air waybill
- Any licences, permits or preference certificates
TEXT,
            ],
            [
                'title' => 'Warehousing',
                'slug' => 'warehousing',
                'icon' => 'warehouse',
                'summary' => 'Short and long term storage, container stripping and stuffing, order picking and stock reporting.',
                'highlights' => ['Short and long term storage', 'Container stripping and stuffing', 'Pick, pack and re-labelling', 'Stock counts and reporting'],
                'description' => <<<'TEXT'
Cargo often needs somewhere to sit: between arrival and clearance, between clearance and delivery, or while an order is being assembled from several suppliers.

## Storage

Goods are received against the packing list, checked for visible damage and put away by consignment. You are told what arrived, in what condition, and what is on hand.

## Handling

- Container stripping and stuffing
- Palletising, shrink wrapping and re-labelling
- Order picking and consolidation of multiple suppliers into one shipment
- Photographs of damaged or short-shipped cargo

## Reporting

Stock movements are recorded as they happen and reported on request, so you always know what is in the building and what has gone out.
TEXT,
            ],
            [
                'title' => 'Door-to-Door Delivery',
                'slug' => 'door-to-door-delivery',
                'icon' => 'truck',
                'summary' => 'One booking covering collection at origin, the main freight leg, customs and final delivery to the receiver.',
                'highlights' => ['Single point of contact', 'Collection and delivery arranged at both ends', 'Customs handled as part of the booking', 'Proof of delivery recorded against the shipment'],
                'description' => <<<'TEXT'
A door to door booking means we take responsibility for the whole movement: the truck at origin, the vessel or aircraft, the customs entry and the final delivery.

## Why it is simpler

There is one booking reference, one invoice and one coordinator who can answer for the whole file. If a step slips, we adjust the next one rather than leaving you to reconcile three different suppliers.

## Delivery

We agree a delivery window with the receiver, confirm access and equipment requirements before the vehicle is dispatched, and record proof of delivery against the shipment file. If a delivery cannot be completed we record the reason and reschedule.
TEXT,
            ],
            [
                'title' => 'Cargo Consolidation',
                'slug' => 'cargo-consolidation',
                'icon' => 'consolidation',
                'summary' => 'Several suppliers, one shipment. We collect, check and combine your orders before they move.',
                'highlights' => ['Multiple suppliers combined into one consignment', 'Goods checked against purchase orders', 'Lower cost per unit than separate shipments', 'One set of documents at destination'],
                'description' => <<<'TEXT'
Buying from several suppliers in the same region usually means several small shipments, several sets of charges and several customs entries. Consolidation replaces that with one movement.

## How it works

Suppliers deliver to our warehouse against your purchase order. We check quantities and packaging as goods arrive, hold them until the order is complete, then load everything into one container or one air consignment.

## What you get

- One transport document and one customs entry at destination
- Fewer handling and terminal charges
- Visibility of what has arrived from each supplier before the shipment departs
- Discrepancies raised while the goods are still with us
TEXT,
            ],
        ];

        foreach ($services as $index => $service) {
            Service::updateOrCreate(
                ['slug' => $service['slug']],
                $service + [
                    'image_alt' => $service['title'].' illustration',
                    'sort_order' => $index + 1,
                    'is_published' => true,
                    'show_on_home' => true,
                    'meta_description' => $service['summary'],
                ],
            );
        }
    }
}

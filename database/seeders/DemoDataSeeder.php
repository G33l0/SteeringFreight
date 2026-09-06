<?php

namespace Database\Seeders;

use App\Enums\ContactStatus;
use App\Enums\ConversationStatus;
use App\Enums\MessageSender;
use App\Enums\QuoteStatus;
use App\Enums\ShippingMethod;
use App\Models\ChatConversation;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\QuoteRequest;
use App\Models\Review;
use App\Models\Shipment;
use App\Models\ShipmentStatus;
use App\Models\User;
use App\Services\TrackingNumberGenerator;
use Illuminate\Database\Seeder;

/**
 * Sample data for development and for looking around a fresh installation.
 *
 * Every record created here is flagged as sample data and labelled as such
 * wherever it appears, so it can never be mistaken for a real customer,
 * shipment or review. Remove it with: php artisan portlane:clear-demo-data
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $staff = User::query()->orderBy('id')->first();
        $statuses = ShipmentStatus::all()->keyBy('slug');
        $generator = app(TrackingNumberGenerator::class);

        $customers = collect([
            ['name' => 'Sample Customer One', 'company' => 'Demo Importers Ltd (sample data)', 'email' => 'sample.one@example.com', 'city' => 'Lagos', 'country' => 'Nigeria'],
            ['name' => 'Sample Customer Two', 'company' => 'Demo Trading BV (sample data)', 'email' => 'sample.two@example.com', 'city' => 'Rotterdam', 'country' => 'Netherlands'],
            ['name' => 'Sample Customer Three', 'company' => 'Demo Manufacturing Co (sample data)', 'email' => 'sample.three@example.com', 'city' => 'Ningbo', 'country' => 'China'],
        ])->map(fn (array $attributes) => Customer::firstOrCreate(
            ['email' => $attributes['email']],
            $attributes + ['is_sample' => true, 'phone' => '+00 000 000 0000', 'created_by' => $staff?->getKey()],
        ));

        $shipments = [
            [
                'status' => 'processing', 'stage' => 3,
                'customer' => 0,
                'origin' => ['Ningbo', 'China'], 'destination' => ['Lagos', 'Nigeria'],
                'method' => ShippingMethod::SeaFreightLcl,
                'location' => 'Ningbo consolidation warehouse',
                'cargo' => 'DEMO DATA. Packaging machinery spares, 12 cartons on 3 pallets.',
                'delivery' => 34,
                'events' => [
                    ['booking-confirmed', 'Ningbo, China', -9, 'Booking confirmed and tracking number issued.'],
                    ['cargo-received', 'Ningbo consolidation warehouse', -5, 'Cargo received at the origin warehouse and checked against the packing list.'],
                    ['processing', 'Ningbo consolidation warehouse', -2, 'Export documentation being prepared.'],
                ],
            ],
            [
                'status' => 'in-transit', 'stage' => 7,
                'customer' => 1,
                'origin' => ['Rotterdam', 'Netherlands'], 'destination' => ['Tema', 'Ghana'],
                'method' => ShippingMethod::SeaFreightFcl,
                'location' => 'Atlantic Ocean',
                'cargo' => 'DEMO DATA. One 40ft container, building materials.',
                'delivery' => 18,
                'container' => 'MSCU4482910', 'vessel' => 'Demo Carrier', 'voyage' => '241W',
                'events' => [
                    ['booking-confirmed', 'Rotterdam, Netherlands', -21, 'Booking confirmed with the carrier.'],
                    ['cargo-received', 'Rotterdam warehouse', -18, 'Container stuffed and sealed.'],
                    ['shipped', 'Port of Rotterdam', -12, 'Container loaded and the vessel has sailed.'],
                    ['in-transit', 'Atlantic Ocean', -4, 'Shipment is currently in transit to the destination port.'],
                ],
            ],
            [
                'status' => 'customs-inspection', 'stage' => 9,
                'customer' => 2,
                'origin' => ['Guangzhou', 'China'], 'destination' => ['Lagos', 'Nigeria'],
                'method' => ShippingMethod::AirFreight,
                'location' => 'Murtala Muhammed International Airport',
                'cargo' => 'DEMO DATA. Electronic components, 6 cartons.',
                'delivery' => 6,
                'awb' => '176-48291735', 'flight' => 'DM204',
                'exception' => 'The consignment has been selected for a routine customs examination at destination. Our broker is attending and we will update this page as soon as the examination is complete.',
                'events' => [
                    ['booking-confirmed', 'Guangzhou, China', -8, 'Booking confirmed.'],
                    ['shipped', 'Guangzhou Baiyun International Airport', -5, 'Consignment departed on the booked flight.'],
                    ['arrived-at-port', 'Murtala Muhammed International Airport', -3, 'Consignment arrived at destination airport.'],
                    ['customs-clearance', 'Lagos, Nigeria', -2, 'Entry lodged with customs at destination.'],
                    ['customs-inspection', 'Lagos, Nigeria', -1, 'The consignment has been selected for a routine customs examination. Our broker is attending.'],
                ],
            ],
            [
                'status' => 'delayed', 'stage' => 8,
                'customer' => 1,
                'origin' => ['Shanghai', 'China'], 'destination' => ['Felixstowe', 'United Kingdom'],
                'method' => ShippingMethod::SeaFreightFcl,
                'location' => 'Port of Felixstowe',
                'cargo' => 'DEMO DATA. Two 20ft containers, ceramic tiles.',
                'delivery' => 11,
                'container' => 'TGHU2298471', 'vessel' => 'Demo Trader', 'voyage' => '118E',
                'exception' => 'Berthing at the destination port is running behind schedule because of congestion. We expect discharge within the next 48 hours and will confirm the revised delivery date once the container is released.',
                'events' => [
                    ['booking-confirmed', 'Shanghai, China', -40, 'Booking confirmed with the carrier.'],
                    ['shipped', 'Port of Shanghai', -34, 'Vessel sailed from the port of loading.'],
                    ['in-transit', 'Indian Ocean', -20, 'Shipment in transit to the destination port.'],
                    ['arrived-at-port', 'Port of Felixstowe', -3, 'Vessel arrived at the destination port.'],
                    ['port-congestion', 'Port of Felixstowe', -1, 'Berthing is delayed because of congestion at the terminal. We expect discharge within 48 hours.'],
                ],
            ],
            [
                'status' => 'out-for-delivery', 'stage' => 13,
                'customer' => 0,
                'origin' => ['Antwerp', 'Belgium'], 'destination' => ['Lagos', 'Nigeria'],
                'method' => ShippingMethod::Multimodal,
                'location' => 'Lagos distribution centre',
                'cargo' => 'DEMO DATA. Mixed consolidation, 18 cartons.',
                'delivery' => 0,
                'events' => [
                    ['booking-confirmed', 'Antwerp, Belgium', -46, 'Booking confirmed.'],
                    ['shipped', 'Port of Antwerp', -38, 'Consignment departed the port of loading.'],
                    ['arrived-at-port', 'Apapa, Lagos', -8, 'Vessel arrived at the destination port.'],
                    ['clearance-completed', 'Lagos, Nigeria', -3, 'Customs formalities completed.'],
                    ['destination-warehouse', 'Lagos distribution centre', -2, 'Cargo received at our destination warehouse.'],
                    ['out-for-delivery', 'Lagos, Nigeria', 0, 'Consignment loaded for final delivery this morning.'],
                ],
            ],
            [
                'status' => 'delivered', 'stage' => 14,
                'customer' => 2,
                'origin' => ['Hamburg', 'Germany'], 'destination' => ['Abidjan', "Côte d'Ivoire"],
                'method' => ShippingMethod::SeaFreightLcl,
                'location' => 'Abidjan',
                'cargo' => 'DEMO DATA. Workshop equipment, 4 crates.',
                'delivery' => -2,
                'events' => [
                    ['booking-confirmed', 'Hamburg, Germany', -52, 'Booking confirmed.'],
                    ['shipped', 'Port of Hamburg', -44, 'Consignment departed the port of loading.'],
                    ['arrived-at-port', 'Port of Abidjan', -12, 'Vessel arrived at the destination port.'],
                    ['clearance-completed', "Abidjan, Côte d'Ivoire", -6, 'Customs formalities completed.'],
                    ['out-for-delivery', 'Abidjan', -3, 'Consignment out for final delivery.'],
                    ['delivered', 'Abidjan', -2, 'Consignment delivered and signed for by the receiver.'],
                ],
            ],
        ];

        foreach ($shipments as $data) {
            $customer = $customers[$data['customer']];

            $shipment = Shipment::firstOrCreate(
                ['tracking_number' => $generator->generate()],
                [
                    'customer_id' => $customer->getKey(),
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'origin_city' => $data['origin'][0],
                    'origin_country' => $data['origin'][1],
                    'destination_city' => $data['destination'][0],
                    'destination_country' => $data['destination'][1],
                    'current_location' => $data['location'],
                    'shipping_method' => $data['method'],
                    'cargo_description' => $data['cargo'],
                    'package_count' => 12,
                    'weight_kg' => 1450.5,
                    'dimensions' => '3 pallets, 120 x 100 x 145 cm',
                    'container_number' => $data['container'] ?? null,
                    'vessel_name' => $data['vessel'] ?? null,
                    'voyage_number' => $data['voyage'] ?? null,
                    'air_waybill_number' => $data['awb'] ?? null,
                    'flight_number' => $data['flight'] ?? null,
                    'estimated_departure' => now()->subDays(14),
                    'estimated_arrival' => now()->addDays(max($data['delivery'] - 3, -5)),
                    'estimated_delivery' => now()->addDays($data['delivery']),
                    'delivered_at' => $data['status'] === 'delivered' ? now()->subDays(2) : null,
                    'shipment_status_id' => $statuses[$data['status']]?->getKey(),
                    'progress_stage' => $data['stage'],
                    'exception_note' => $data['exception'] ?? null,
                    'internal_notes' => 'Sample record created by the demo data seeder.',
                    'is_sample' => true,
                    'status_updated_at' => now(),
                    'created_by' => $staff?->getKey(),
                    'updated_by' => $staff?->getKey(),
                ],
            );

            if ($shipment->events()->exists()) {
                continue;
            }

            foreach ($data['events'] as [$slug, $location, $days, $description]) {
                $shipment->events()->create([
                    'shipment_status_id' => $statuses[$slug]?->getKey(),
                    'location' => $location,
                    'occurred_at' => now()->addDays($days)->setTime(9, 30),
                    'description' => $description,
                    'is_public' => true,
                    'created_by' => $staff?->getKey(),
                ]);
            }
        }

        // One conversation so the messages screen is not empty.
        $conversationShipment = Shipment::where('is_sample', true)->latest('id')->first();

        if ($conversationShipment && ! ChatConversation::where('shipment_id', $conversationShipment->getKey())->exists()) {
            $conversation = ChatConversation::create([
                'shipment_id' => $conversationShipment->getKey(),
                'customer_id' => $conversationShipment->customer_id,
                'subject' => 'Sample data: delivery window',
                'contact_name' => $conversationShipment->customer_name,
                'contact_email' => $conversationShipment->customer_email,
                'status' => ConversationStatus::Open,
                'last_message_at' => now()->subHours(3),
                'unread_for_staff' => 1,
            ]);

            $conversation->messages()->create([
                'sender_type' => MessageSender::Customer,
                'sender_name' => $conversationShipment->customer_name,
                'body' => 'Sample message. Could you confirm the delivery window for this consignment?',
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ]);
        }

        $reviews = [
            ['Sample Review One', 'Demo Importers Ltd', 'Lagos, Nigeria', 5, 'Sample content for demonstration. Documents were checked before the container was stuffed, which meant clearance went through without a query.'],
            ['Sample Review Two', 'Demo Trading BV', 'Rotterdam, Netherlands', 5, 'Sample content for demonstration. We always know who is handling the file and the tracking page matches what we are told on the phone.'],
            ['Sample Review Three', 'Demo Manufacturing Co', 'Ningbo, China', 4, 'Sample content for demonstration. A vessel was rolled and we were told the same day, with a revised delivery date the next morning.'],
        ];

        foreach ($reviews as $index => [$name, $company, $location, $rating, $body]) {
            Review::firstOrCreate(
                ['customer_name' => $name],
                [
                    'company' => $company,
                    'location' => $location,
                    'rating' => $rating,
                    'body' => $body,
                    'is_published' => true,
                    'is_sample' => true,
                    'sort_order' => $index + 1,
                    'reviewed_on' => now()->subMonths($index + 1),
                    'created_by' => $staff?->getKey(),
                ],
            );
        }

        QuoteRequest::firstOrCreate(
            ['email' => 'sample.quote@example.com'],
            [
                'name' => 'Sample Quote Request',
                'phone' => '+00 000 000 0000',
                'company' => 'Demo Trading BV (sample data)',
                'origin' => 'Shanghai, China',
                'destination' => 'Tema, Ghana',
                'shipping_method' => ShippingMethod::SeaFreightLcl,
                'cargo_type' => 'Sample data. Kitchen equipment.',
                'approximate_weight' => '850 kg',
                'package_count' => 9,
                'message' => 'Sample quote request created by the demo data seeder.',
                'status' => QuoteStatus::New,
            ],
        );

        ContactMessage::firstOrCreate(
            ['email' => 'sample.contact@example.com'],
            [
                'name' => 'Sample Contact Message',
                'phone' => '+00 000 000 0000',
                'subject' => 'Sample data: question about warehousing',
                'message' => 'Sample message created by the demo data seeder. How long can cargo stay in your warehouse before storage is charged?',
                'status' => ContactStatus::New,
            ],
        );
    }
}

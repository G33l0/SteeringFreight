<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['How do I track my shipment?', "Enter the tracking number from your booking confirmation on the tracking page. The number looks like PLS-48291735.\n\nThe page shows the current status, the last reported location, the estimated delivery date and the full history of updates recorded by our team.", 'Tracking', true],
            ['How often is tracking updated?', 'Updates are entered by the coordinator handling your file as the shipment reaches each stage. On a long sea leg there can be several days between updates because nothing has changed; on the delivery leg they are usually the same day.', 'Tracking', true],
            ['What do I do if the tracking page says the shipment is delayed?', 'Open the tracking page and read the update: exceptions always carry an explanation written by the person handling the file. If you need more detail, use the contact button on the tracking page and the message goes to that same team.', 'Tracking', false],
            ['How long does sea freight take?', "Transit time depends on the lane, the carrier's rotation and whether the service is direct or routed through a transhipment hub. We quote door to door time, including collection, clearance and delivery, rather than just the sailing time.", 'Sea Freight', true],
            ['What is the difference between FCL and LCL?', 'FCL means you take a whole container. LCL means your cargo shares a container with other shipments and you pay for the space it uses. FCL is usually cheaper per unit once you fill roughly half a container.', 'Sea Freight', false],
            ['How is air freight priced?', 'On chargeable weight, which is the greater of the actual gross weight and the volumetric weight. Light but bulky cargo is charged on volume, so send dimensions along with the weight when you ask for a rate.', 'Air Freight', true],
            ['What documents do you need for customs clearance?', "At minimum: the commercial invoice, the packing list and the transport document. Some goods also need a licence, permit, certificate of origin or health certificate.\n\nSend them as early as you can. Most border delays are caused by document problems that could have been fixed before the cargo moved.", 'Customs Clearance', true],
            ['Who pays duties and taxes?', 'That depends on the terms of sale agreed with your supplier. We calculate the expected duty and tax before arrival so there are no surprises, and we tell you what is payable and when.', 'Customs Clearance', false],
            ['Can you collect from my supplier?', 'Yes. On a door to door booking we arrange collection at origin, the main freight leg, customs clearance and final delivery under one reference.', 'Door-to-Door Delivery', false],
            ['Do you store cargo?', 'Yes. We handle short and long term storage, container stripping and stuffing, order picking and consolidation of several suppliers into a single shipment.', 'Warehousing', false],
            ['How do I get a quote?', 'Use the quote request form with the origin, destination, cargo description and approximate weight. If you have dimensions and an HS code, include them; the more accurate the information, the firmer the rate.', 'Bookings', false],
        ];

        foreach ($faqs as $index => [$question, $answer, $category, $onHome]) {
            Faq::updateOrCreate(
                ['question' => $question],
                [
                    'answer' => $answer,
                    'category' => $category,
                    'sort_order' => $index + 1,
                    'is_published' => true,
                    'show_on_home' => $onHome,
                ],
            );
        }
    }
}

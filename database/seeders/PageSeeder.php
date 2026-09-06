<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * The pages linked from the site navigation and footer.
 *
 * The privacy and terms text is a workable starting point written in plain
 * language. Review both with your own legal adviser and edit them in the admin
 * panel before the site goes live.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $company = (string) config('portlane.company.name');

        $pages = [
            [
                'title' => 'About us',
                'slug' => 'about',
                'intro' => 'A freight forwarder and customs broker handling sea, air and road movements for importers and exporters.',
                'body' => <<<TEXT
{$company} arranges freight, clears customs and delivers cargo. We work with importers, exporters and manufacturers who need their consignments to arrive when they said they would, and who would rather deal with one company than four.

## How we work

Every booking is assigned to a coordinator. That person books the space, prepares the documents, watches the schedule and answers the phone when you call about it. Nothing is handed to a different department at the border.

## What that means in practice

- We check documents before the cargo moves, because that is where most delays begin.
- We record tracking milestones as the file progresses, so the tracking page reflects the real position.
- When a schedule slips, we tell you what it means for the delivery date instead of waiting to be asked.

## Services

We handle sea freight, air freight, customs clearance, warehousing, door to door delivery and cargo consolidation. Most clients use more than one of them, which is the point: the same team sees the shipment from collection to delivery.

## Getting in touch

Send us a route, a cargo description and an approximate weight and we will come back with a rate and a realistic transit time.
TEXT,
                'is_system' => true,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'intro' => 'How we collect, use and protect the personal information you give us.',
                'body' => <<<TEXT
This policy explains what personal information {$company} collects through this website, why we collect it and what we do with it.

## Information we collect

- **Contact and quotation forms.** Your name, email address, telephone number, company name and the shipment details you send us.
- **Shipment records.** The contact details recorded against a shipment by our staff so that we can carry out the transport instruction.
- **Messages.** Messages you send us from the tracking page, including any files you attach.
- **Technical information.** The IP address a form was submitted from, used to limit abuse of the forms, and standard web server logs.

## Why we use it

We use the information to prepare quotations, to carry out and account for transport instructions, to comply with customs and transport law, and to answer your questions. We do not sell it, and we do not use it for advertising.

## Who we share it with

Information is shared with the parties needed to move your cargo: carriers, airlines, ports, terminals, hauliers, warehouse operators, and the customs and regulatory authorities of the countries involved. We share only what those parties need.

## How long we keep it

Shipment records and the documents that go with them are kept for as long as customs and commercial law requires. Quotation requests and website enquiries are kept while they are useful for the enquiry and its follow up, and are removed after that.

## Cookies

This website sets a session cookie so that forms work correctly and so that a shipment you have looked up stays available to you during your visit. We do not use advertising or tracking cookies.

## Your rights

You can ask us what personal information we hold about you, ask for it to be corrected, or ask for it to be deleted where we are not required to keep it. Write to us using the contact details on the contact page.

## Changes

If this policy changes, the updated version is published on this page with a new revision date.
TEXT,
                'is_system' => true,
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'intro' => 'The terms that apply to this website and to freight services booked through it.',
                'body' => <<<'TEXT'
These terms apply to your use of this website and to enquiries and bookings made through it.

## Using this website

The information on this site is provided for general guidance. Rates, schedules and transit times shown or quoted are indicative until confirmed in writing for a specific consignment.

## Quotations

A quotation is based on the information supplied at the time of the request. If the actual weight, volume, commodity or terms differ from what was quoted, the rate may change. Quotations exclude duties, taxes and charges imposed by authorities unless we state otherwise.

## Bookings

A booking is accepted when we confirm it in writing. You are responsible for the accuracy and completeness of the information and documents you supply, including the description, value and classification of the goods.

## Prohibited and restricted goods

You must tell us in advance if a consignment contains dangerous goods, goods requiring a licence or permit, or goods that are restricted in any country on the route. We may refuse or return a consignment that has been declared incorrectly.

## Liability

Freight services are subject to the standard trading conditions and the international conventions that apply to the transport used, which limit liability by weight or by consignment. Copies of the applicable conditions are available on request. Nothing in these terms excludes liability that cannot be excluded by law.

## Tracking information

Tracking information is recorded by our staff as a shipment progresses and reflects the position known at the time of the update. It is provided for information and does not vary the terms of carriage.

## Payment

Invoices are payable in accordance with the credit terms agreed in writing. Where no terms have been agreed, charges are payable before release of the consignment.

## Governing law

The governing law and jurisdiction are those stated in the trading conditions applying to the booking.

## Contact

Questions about these terms can be sent using the details on the contact page.
TEXT,
                'is_system' => true,
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                $page + ['is_published' => true, 'published_at' => now()],
            );
        }
    }
}

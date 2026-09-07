<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * The pages linked from the site navigation and footer.
 *
 * The privacy and terms copy is a plain-language draft, not legal advice. Both
 * documents pull their business specific details from the site settings through
 * [[placeholders]], and any line whose placeholder is still empty is left out
 * of the rendered page. Have your own legal adviser review both before launch,
 * then tick "Legal pages reviewed" under Site settings.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About us',
                'slug' => 'about',
                'intro' => 'A freight forwarder and customs broker moving sea, air and road cargo for importers and exporters.',
                'body' => <<<'TEXT'
[[company.name]] arranges freight, prepares customs entries and delivers cargo. We work with importers, exporters and manufacturers who need to know where a consignment is, what it will cost and when it will arrive.

## How a booking is handled

Every booking is assigned to a coordinator. That person books the space, prepares the documents, watches the schedule and answers the phone when you call about it. Nothing is handed to a different department at the border.

Before cargo moves we check the commercial invoice, the packing list and the transport document against each other. Most delays start as a document problem, and a document problem is far cheaper to fix while the goods are still in the warehouse.

## How cargo is handled

Consignments are checked against the packing list when we receive them, and anything that arrives short, damaged or badly packed is photographed and raised with you the same day. Cargo is packed for the journey it is actually making, secured for the leg it is on, and stored under cover while it waits.

## How you receive updates

When a booking is confirmed we issue a tracking number in the form PLS-48291735. Each stage of the movement is recorded against it by the coordinator handling the file: collection, departure, arrival, customs, release and delivery.

You can open the tracking page at any time to see the current status, the last reported location, the delivery estimate and the full history. If something needs explaining, the same page has a message box that reaches the team working on your shipment rather than a general inbox.

## Documentation and communication

Two things decide whether a shipment runs smoothly: the paperwork being right before departure, and somebody telling you early when a schedule changes. We would rather call with an inconvenient update than let a delivery date slip quietly.

## Talk to us

Send us the origin, the destination, a description of the cargo and an approximate weight. We will come back with a rate and a realistic transit time.
TEXT,
                'is_system' => true,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'intro' => 'How we collect, use and protect the information you give us.',
                'body' => <<<'TEXT'
This policy explains what personal information [[company.legal_name]] collects through this website, why we collect it and what we do with it. It is written in plain language rather than legal drafting.

## Information we collect

- **Contact and quotation forms.** Your name, email address, telephone number, company name and the shipment details you send us.
- **Shipment records.** The contact details recorded against a shipment by our staff so that we can carry out the transport instruction.
- **Messages.** Messages you send from the tracking page. The tracking page chat does not accept files, and the conversation is deleted automatically 24 hours after the last message.
- **Technical information.** The IP address a form was submitted from, used to limit abuse of the forms, and standard web server logs.

## Why we use it

We use the information to prepare quotations, to carry out and account for transport instructions, to meet customs and transport requirements, and to answer your questions. We do not sell it and we do not use it for advertising.

## Who we share it with

Information is shared with the parties needed to move your cargo: carriers, airlines, ports, terminals, hauliers, warehouse operators, and the customs and regulatory authorities of the countries involved. We share only what those parties need.

## How long we keep it

Shipment records and the documents that go with them are kept for [[legal.retention_period]]. Quotation requests and website enquiries are kept while they are useful for the enquiry and its follow up, and are removed after that.

Conversations in the tracking page chat are the exception: they are deleted, with every message in them, 24 hours after the last message. Anything from a conversation that we have to keep in order to move your cargo is recorded on the shipment itself before then.

## Cookies

This website sets a session cookie so that forms work correctly and so that a shipment you have looked up stays available to you during your visit. We do not use advertising or tracking cookies.

## Your rights

You can ask what personal information we hold about you, ask for it to be corrected, or ask for it to be deleted where we are not required to keep it.

Write to us at [[legal.contact_email]].

Our postal address is [[contact.address]].

## Changes

If this policy changes, the updated version is published on this page.
TEXT,
                'is_system' => true,
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'intro' => 'The terms that apply to this website and to freight services booked through it.',
                'body' => <<<'TEXT'
These terms apply to your use of this website and to enquiries and bookings made with [[company.legal_name]].

## Using this website

The information on this site is provided for general guidance. Rates, schedules and transit times shown or quoted are indicative until confirmed in writing for a specific consignment.

## Quotations

A quotation is based on the information supplied at the time of the request. If the actual weight, volume, commodity or terms differ from what was quoted, the rate may change. Quotations exclude duties, taxes and charges imposed by authorities unless we state otherwise.

## Bookings

A booking is accepted when we confirm it in writing. You are responsible for the accuracy and completeness of the information and documents you supply, including the description, value and classification of the goods.

## Prohibited and restricted goods

You must tell us in advance if a consignment contains dangerous goods, goods requiring a licence or permit, or goods that are restricted in any country on the route. We may refuse or return a consignment that has been declared incorrectly.

## Liability

Freight services are subject to the standard trading conditions that apply to the booking and to the international conventions governing the transport used, which limit liability by weight or by consignment. Nothing in these terms excludes liability that cannot be excluded by law.

Our bookings are subject to [[legal.trading_conditions]], a copy of which is available on request.

## Tracking information

Tracking information is recorded by our staff as a shipment progresses and reflects the position known at the time of the update. It is provided for information and does not vary the terms of carriage. We do not guarantee a delivery date unless we have agreed one with you in writing.

## Payment

Invoices are payable in accordance with the credit terms agreed in writing. Where no terms have been agreed, charges are payable before release of the consignment.

## Governing law

These terms are governed by the law of [[legal.jurisdiction]], and the courts of that jurisdiction have exclusive jurisdiction over any dispute.

## The company

[[company.legal_name]], registration number [[company.registration_number]].

## Contact

Questions about these terms can be sent to [[contact.email]].
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

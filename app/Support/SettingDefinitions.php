<?php

namespace App\Support;

/**
 * The editable site settings, their group, input type and default value.
 *
 * Everything the public website prints that is not shipment data comes from
 * here, so the company name, contact details, branding and page copy can be
 * changed from the admin panel without touching a template.
 *
 * Values that would misrepresent the business if invented (address, telephone,
 * registration number, operating lanes) ship empty. The website hides those
 * sections until they are filled in, and the admin dashboard lists what is
 * still missing.
 */
class SettingDefinitions
{
    public const TYPE_STRING = 'string';

    public const TYPE_TEXT = 'text';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_JSON = 'json';

    public const TYPE_IMAGE = 'image';

    public const TYPE_COLOUR = 'colour';

    /**
     * @return array<string, array{group: string, type: string, label: string, help?: string, default: mixed, rows?: int, launch?: bool}>
     */
    public static function all(): array
    {
        return [
            // ---------------------------------------------------------------
            // Company
            // ---------------------------------------------------------------
            'company.name' => [
                'group' => 'company', 'type' => self::TYPE_STRING, 'label' => 'Company name',
                'help' => 'Shown in the header, footer, page titles and customer emails.',
                'default' => config('portlane.company.name'),
            ],
            'company.legal_name' => [
                'group' => 'company', 'type' => self::TYPE_STRING, 'label' => 'Registered name',
                'help' => 'The name the business is registered under. Used in the footer copyright line and the legal pages.',
                'default' => config('portlane.company.name'),
            ],
            'company.registration_number' => [
                'group' => 'company', 'type' => self::TYPE_STRING, 'label' => 'Company registration number',
                'help' => 'Optional. Left empty until you have one; the legal pages leave the line out entirely when it is blank.',
                'default' => null,
            ],
            'company.tagline' => [
                'group' => 'company', 'type' => self::TYPE_STRING, 'label' => 'Tagline',
                'default' => config('portlane.company.tagline'),
            ],
            'company.intro' => [
                'group' => 'company', 'type' => self::TYPE_TEXT, 'label' => 'Company description',
                'help' => 'Two or three sentences. Used in the footer, on the about page and in search results.',
                'default' => "Portlane Shipping arranges sea and air freight, handles customs entries and delivers cargo to the receiver's door. Every booking is run by a named coordinator who prepares the documents, watches the schedule and keeps the tracking record current.",
                'rows' => 4,
            ],
            'company.footer_note' => [
                'group' => 'company', 'type' => self::TYPE_TEXT, 'label' => 'Footer note',
                'help' => 'Optional line under the footer contact details, for a licence reference or a short disclaimer.',
                'default' => null,
                'rows' => 2,
            ],
            'company.logo' => [
                'group' => 'company', 'type' => self::TYPE_IMAGE, 'label' => 'Logo',
                'help' => 'Optional. Replaces the built in Portlane logo in the header, footer and admin panel. SVG or PNG with a transparent background.',
                'default' => null,
            ],
            'company.about_image' => [
                'group' => 'company', 'type' => self::TYPE_IMAGE, 'label' => 'About page banner',
                'help' => 'Optional. A wide photograph across the top of the About page, about two and a half times as wide as it is tall. Leave empty to use the photograph bundled with the application.',
                'default' => null,
            ],

            // ---------------------------------------------------------------
            // Contact and hours
            // ---------------------------------------------------------------
            'contact.email' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'General email',
                'help' => 'Published on the website. Leave empty until the address exists.',
                'default' => null, 'launch' => true,
            ],
            'contact.operations_email' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Operations email',
                'help' => 'Published on the contact page for booking and shipment queries. Falls back to the general email.',
                'default' => null,
            ],
            'contact.phone' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Telephone',
                'help' => 'Published on the website. Leave empty until the line is live.',
                'default' => null, 'launch' => true,
            ],
            'contact.address_line_1' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Address line 1',
                'default' => null, 'launch' => true,
            ],
            'contact.address_line_2' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Address line 2',
                'default' => null,
            ],
            'contact.city' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'City',
                'default' => null, 'launch' => true,
            ],
            'contact.region' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'State or region',
                'default' => null,
            ],
            'contact.postal_code' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Postal code',
                'default' => null,
            ],
            'contact.country' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Country',
                'default' => null, 'launch' => true,
            ],
            'contact.timezone' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Operating timezone',
                'help' => 'Shown beside the opening hours, for example "West Africa Standard Time". The timezone the application stores dates in is set with APP_TIMEZONE in the .env file.',
                'default' => config('app.timezone'),
            ],
            'contact.hours_weekdays' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Opening hours, Monday to Friday',
                'default' => '08:00 - 18:00',
            ],
            'contact.hours_saturday' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Opening hours, Saturday',
                'default' => '09:00 - 13:00',
            ],
            'contact.hours_sunday' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Opening hours, Sunday',
                'default' => 'Closed',
            ],
            'contact.hours_note' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Opening hours note',
                'default' => 'Times shown are local to our operations desk.',
            ],
            'contact.extra_details' => [
                'group' => 'contact', 'type' => self::TYPE_JSON, 'label' => 'Additional contact details',
                'help' => 'One per line, written as "Label | Detail". For example "WhatsApp | +234 000 000 0000" or "Lagos branch | 12 Wharf Road, Apapa". These appear on the contact page and on the tracking page help panel.',
                'default' => [],
            ],
            'contact.response_note' => [
                'group' => 'contact', 'type' => self::TYPE_TEXT, 'label' => 'Contact form note',
                'default' => 'Send us the details of your shipment and a coordinator will come back to you during business hours.',
                'rows' => 3,
            ],

            // ---------------------------------------------------------------
            // Homepage
            // ---------------------------------------------------------------
            'home.hero_eyebrow' => [
                'group' => 'home', 'type' => self::TYPE_STRING, 'label' => 'Hero eyebrow',
                'default' => 'Freight forwarding and customs brokerage',
            ],
            'home.hero_heading' => [
                'group' => 'home', 'type' => self::TYPE_STRING, 'label' => 'Hero heading',
                'default' => 'Freight handled with care, from origin to destination',
            ],
            'home.hero_intro' => [
                'group' => 'home', 'type' => self::TYPE_TEXT, 'label' => 'Hero paragraph',
                'default' => 'We arrange sea and air freight, prepare the customs paperwork and deliver to the door. You get one point of contact and a tracking record you can check at any time.',
                'rows' => 3,
            ],
            'home.hero_image' => [
                'group' => 'home', 'type' => self::TYPE_IMAGE, 'label' => 'Hero photograph',
                'help' => 'Optional. A wide photograph of your own operation, cargo or vehicles, twice as wide as it is tall. Keep the left third free of detail: the headline sits over it. Leave empty to use the photograph bundled with the application.',
                'default' => null,
            ],
            'home.services_heading' => [
                'group' => 'home', 'type' => self::TYPE_STRING, 'label' => 'Services section heading',
                'default' => 'What we handle',
            ],
            'home.services_intro' => [
                'group' => 'home', 'type' => self::TYPE_TEXT, 'label' => 'Services section intro',
                'default' => 'Booking, documents, clearance and delivery are handled by the same team, so nothing is passed between agents at the border.',
                'rows' => 2,
            ],
            'home.why_heading' => [
                'group' => 'home', 'type' => self::TYPE_STRING, 'label' => 'Why clients stay with us, heading',
                'default' => 'Why clients stay with us',
            ],
            'home.why_points' => [
                'group' => 'home', 'type' => self::TYPE_JSON, 'label' => 'Why clients stay with us, points',
                'help' => 'One point per line, written as "Title | Explanation".',
                'default' => [
                    ['title' => 'Clear shipment updates', 'body' => 'Milestones are entered by the coordinator handling the cargo, so the tracking page shows the real position rather than an automated guess.'],
                    ['title' => 'Careful cargo handling', 'body' => 'Consignments are checked against the packing list on receipt, packed for the journey they are making, and photographed when something does not look right.'],
                    ['title' => 'Responsive communication', 'body' => 'You can message the team from the tracking page and get an answer from the person who booked the shipment.'],
                    ['title' => 'Straightforward documentation', 'body' => 'Invoices, packing lists and transport documents are checked against each other before the cargo moves, which is where most delays are avoided.'],
                    ['title' => 'Flexible freight options', 'body' => 'Full container, groupage, air or road, quoted side by side so you can weigh cost against transit time.'],
                    ['title' => 'Attention to delivery details', 'body' => 'Access, equipment and delivery windows are confirmed with the receiver before a vehicle is dispatched.'],
                ],
            ],
            'home.destinations_heading' => [
                'group' => 'home', 'type' => self::TYPE_STRING, 'label' => 'Destinations heading',
                'default' => 'Where we ship',
            ],
            'home.destinations_intro' => [
                'group' => 'home', 'type' => self::TYPE_TEXT, 'label' => 'Destinations intro',
                'default' => 'Ask us about the route you need and we will tell you the options, the transit time and what the paperwork requires.',
                'rows' => 2,
            ],
            'home.destinations' => [
                'group' => 'home', 'type' => self::TYPE_JSON, 'label' => 'Destination lanes',
                'help' => 'Empty on purpose: add only lanes you actually operate, one per line, written as "Region | Countries served, separated by commas". Country names are matched against the country list and shown with their flag; anything else is printed exactly as you write it. The section stays hidden while this is empty.',
                'default' => [], 'launch' => true,
            ],
            'home.reviews_heading' => [
                'group' => 'home', 'type' => self::TYPE_STRING, 'label' => 'Client reviews heading',
                'default' => 'What clients say',
            ],
            'home.reviews_intro' => [
                'group' => 'home', 'type' => self::TYPE_TEXT, 'label' => 'Client reviews intro',
                'help' => 'Shown above the reviews on the homepage and at the top of the client reviews page. The section stays hidden until you publish a review.',
                'default' => 'Feedback from the importers, exporters and forwarding partners we work with, published as we receive it.',
                'rows' => 2,
            ],
            'home.cta_heading' => [
                'group' => 'home', 'type' => self::TYPE_STRING, 'label' => 'Closing call to action heading',
                'default' => 'Have cargo to move?',
            ],
            'home.cta_body' => [
                'group' => 'home', 'type' => self::TYPE_TEXT, 'label' => 'Closing call to action text',
                'default' => 'Send us the origin, destination and a description of the cargo. We will come back with a rate and a realistic transit time.',
                'rows' => 2,
            ],

            // ---------------------------------------------------------------
            // Tracking and chat
            // ---------------------------------------------------------------
            'tracking.prefix' => [
                'group' => 'tracking', 'type' => self::TYPE_STRING, 'label' => 'Tracking number prefix',
                'help' => 'Used when new tracking numbers are generated, for example PLS-48291735. Numbers already issued are never rewritten.',
                'default' => config('portlane.tracking.prefix'),
            ],
            'tracking.digits' => [
                'group' => 'tracking', 'type' => self::TYPE_INTEGER, 'label' => 'Digits after the prefix',
                'default' => config('portlane.tracking.digits'),
            ],
            'tracking.intro' => [
                'group' => 'tracking', 'type' => self::TYPE_TEXT, 'label' => 'Tracking page intro',
                'default' => 'Enter the tracking number from your booking confirmation to see the current status of your shipment.',
                'rows' => 2,
            ],
            'tracking.support_note' => [
                'group' => 'tracking', 'type' => self::TYPE_TEXT, 'label' => 'Support note on the tracking page',
                'default' => 'Something not looking right? Message the team handling your shipment and we will check the file.',
                'rows' => 2,
            ],
            'tracking.chat_enabled' => [
                'group' => 'tracking', 'type' => self::TYPE_BOOLEAN, 'label' => 'Allow customers to message the team from the tracking page',
                'default' => true,
            ],
            'tracking.chat_poll_interval' => [
                'group' => 'tracking', 'type' => self::TYPE_INTEGER, 'label' => 'Chat refresh interval, in milliseconds',
                'help' => 'How often an open tracking page checks for new replies. 8000 (eight seconds) is comfortable on shared hosting; lower it only if your host can take the traffic.',
                'default' => config('portlane.chat.poll_interval'),
            ],
            'uploads.max_kb' => [
                'group' => 'tracking', 'type' => self::TYPE_INTEGER, 'label' => 'Maximum upload size, in kilobytes',
                'help' => 'Applies to shipment documents uploaded in the admin panel. Files cannot be sent through the customer chat at all. Your hosting also enforces its own limit through upload_max_filesize.',
                'default' => config('portlane.uploads.max_kb'),
            ],

            // ---------------------------------------------------------------
            // Quote requests
            // ---------------------------------------------------------------
            'quotes.intro' => [
                'group' => 'quotes', 'type' => self::TYPE_TEXT, 'label' => 'Quote page intro',
                'default' => 'Tell us where the cargo is, where it needs to go and what it is. We will come back with a rate and a realistic transit time.',
                'rows' => 2,
            ],
            'quotes.confirmation' => [
                'group' => 'quotes', 'type' => self::TYPE_TEXT, 'label' => 'Message shown after a request is sent',
                'help' => 'The reference number is added to the end automatically.',
                'default' => 'Thank you. Your request has reached our operations desk and a coordinator will reply by email.',
                'rows' => 2,
            ],
            'quotes.frequent_countries' => [
                'group' => 'quotes', 'type' => self::TYPE_JSON, 'label' => 'Frequently shipped countries',
                'help' => 'Shown at the top of the country lists on the quote form, one per line. Must match the country names in the list, for example "China" or "United Arab Emirates".',
                'default' => [],
            ],
            'quotes.reply_signature' => [
                'group' => 'quotes', 'type' => self::TYPE_TEXT, 'label' => 'Default text for a quotation reply',
                'help' => 'Pre-filled in the reply box on a quote request, so a coordinator only has to change the specifics.',
                'default' => "Thank you for your enquiry.\n\nThe rate above covers the main freight leg, terminal handling at both ends and the customs entry at destination. It excludes duty, taxes and any storage after the free period.\n\nTo book, send us the commercial invoice and packing list and confirm the collection address. We will issue the booking and a tracking number the same day.",
                'rows' => 8,
            ],

            // ---------------------------------------------------------------
            // Notifications
            // ---------------------------------------------------------------
            'notifications.enabled' => [
                'group' => 'notifications', 'type' => self::TYPE_BOOLEAN, 'label' => 'Send shipment update emails to customers',
                'help' => 'Off until your mail settings are configured and tested. When on, emails are only sent for statuses that have notifications switched on under Tracking statuses, and only when the shipment has an email address.',
                'default' => false, 'launch' => true,
            ],
            'notifications.admin_email' => [
                'group' => 'notifications', 'type' => self::TYPE_STRING, 'label' => 'Internal notification address',
                'help' => 'Quote requests, contact messages and new customer chats are copied to this address.',
                'default' => config('portlane.notifications.admin_email'),
                'launch' => true,
            ],
            'notifications.signature' => [
                'group' => 'notifications', 'type' => self::TYPE_TEXT, 'label' => 'Email sign off',
                'default' => 'Operations desk',
                'rows' => 2,
            ],

            // ---------------------------------------------------------------
            // Legal
            // ---------------------------------------------------------------
            'legal.jurisdiction' => [
                'group' => 'legal', 'type' => self::TYPE_STRING, 'label' => 'Governing jurisdiction',
                'help' => 'The country or state whose law governs your contracts, for example "England and Wales". Used in the terms of service.',
                'default' => null, 'launch' => true,
            ],
            'legal.trading_conditions' => [
                'group' => 'legal', 'type' => self::TYPE_STRING, 'label' => 'Standard trading conditions',
                'help' => 'The conditions your bookings are subject to, for example the local freight forwarders association conditions. Leave empty and the line is left out.',
                'default' => null,
            ],
            'legal.retention_period' => [
                'group' => 'legal', 'type' => self::TYPE_STRING, 'label' => 'Document retention period',
                'help' => 'How long shipment records and customs documents are kept, for example "six years".',
                'default' => 'the period required by customs and commercial law',
            ],
            'legal.contact_email' => [
                'group' => 'legal', 'type' => self::TYPE_STRING, 'label' => 'Privacy and legal contact address',
                'help' => 'Where data protection requests should be sent. Falls back to the general email address.',
                'default' => null,
            ],
            'legal.reviewed' => [
                'group' => 'legal', 'type' => self::TYPE_BOOLEAN, 'label' => 'Legal pages reviewed by our adviser',
                'help' => 'The privacy policy and terms of service that ship with the application are plain-language drafts. Tick this once your own legal adviser has reviewed and approved them.',
                'default' => false, 'launch' => true,
            ],

            // ---------------------------------------------------------------
            // Brand
            // ---------------------------------------------------------------
            'brand.primary_colour' => [
                'group' => 'brand', 'type' => self::TYPE_COLOUR, 'label' => 'Primary colour',
                'help' => 'The dark colour used for headers, footers and the admin panel.',
                'default' => config('portlane.brand.primary'),
            ],
            'brand.accent_colour' => [
                'group' => 'brand', 'type' => self::TYPE_COLOUR, 'label' => 'Accent colour',
                'help' => 'Used for buttons, links and highlights. Keep it dark enough for white text to stay readable.',
                'default' => config('portlane.brand.accent'),
            ],

            // ---------------------------------------------------------------
            // Search and social
            // ---------------------------------------------------------------
            'seo.meta_description' => [
                'group' => 'seo', 'type' => self::TYPE_TEXT, 'label' => 'Default meta description',
                'default' => 'Sea freight, air freight, customs clearance, warehousing and door to door delivery, with shipment tracking on every booking.',
                'rows' => 3,
            ],
            'seo.og_image' => [
                'group' => 'seo', 'type' => self::TYPE_IMAGE, 'label' => 'Social sharing image',
                'help' => 'Shown when a link to the site is posted on social platforms. 1200 x 630 pixels. Leave empty to use the built in Portlane card.',
                'default' => null,
            ],
            'seo.indexable' => [
                'group' => 'seo', 'type' => self::TYPE_BOOLEAN, 'label' => 'Allow search engines to index the site',
                'default' => true,
            ],
            'seo.linkedin_url' => [
                'group' => 'seo', 'type' => self::TYPE_STRING, 'label' => 'LinkedIn page URL',
                'default' => null,
            ],
            'seo.facebook_url' => [
                'group' => 'seo', 'type' => self::TYPE_STRING, 'label' => 'Facebook page URL',
                'default' => null,
            ],
            'seo.x_url' => [
                'group' => 'seo', 'type' => self::TYPE_STRING, 'label' => 'X (Twitter) profile URL',
                'default' => null,
            ],
        ];
    }

    /** @return array<string, string> */
    public static function groups(): array
    {
        return [
            'company' => 'Company',
            'contact' => 'Contact and hours',
            'home' => 'Homepage',
            'tracking' => 'Tracking and chat',
            'quotes' => 'Quote requests',
            'notifications' => 'Notifications',
            'legal' => 'Legal',
            'brand' => 'Brand',
            'seo' => 'Search and social',
        ];
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return collect(self::all())->map(fn (array $definition) => $definition['default'])->all();
    }

    /**
     * Settings a new installation should fill in before it goes live.
     *
     * @return array<string, array{group: string, type: string, label: string, help?: string, default: mixed, rows?: int, launch?: bool}>
     */
    public static function launchChecklist(): array
    {
        return collect(self::all())->filter(fn (array $definition) => $definition['launch'] ?? false)->all();
    }

    /** @return array{group: string, type: string, label: string, help?: string, default: mixed, rows?: int, launch?: bool}|null */
    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}

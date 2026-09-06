<?php

namespace App\Support;

/**
 * The editable site settings, their group, input type and default value.
 *
 * Everything the public website prints that is not shipment data comes from
 * here, so the company name, contact details and homepage copy can be changed
 * without touching a template.
 */
class SettingDefinitions
{
    public const TYPE_STRING = 'string';

    public const TYPE_TEXT = 'text';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_JSON = 'json';

    public const TYPE_IMAGE = 'image';

    /**
     * @return array<string, array{group: string, type: string, label: string, help?: string, default: mixed, rows?: int}>
     */
    public static function all(): array
    {
        return [
            // Company identity.
            'company.name' => [
                'group' => 'company', 'type' => self::TYPE_STRING, 'label' => 'Company name',
                'help' => 'Shown in the header, footer, page titles and customer emails.',
                'default' => config('portlane.company.name'),
            ],
            'company.legal_name' => [
                'group' => 'company', 'type' => self::TYPE_STRING, 'label' => 'Registered name',
                'help' => 'Used in the footer copyright line and legal pages.',
                'default' => config('portlane.company.legal_name'),
            ],
            'company.tagline' => [
                'group' => 'company', 'type' => self::TYPE_STRING, 'label' => 'Tagline',
                'default' => config('portlane.company.tagline'),
            ],
            'company.intro' => [
                'group' => 'company', 'type' => self::TYPE_TEXT, 'label' => 'Short company description',
                'help' => 'Two or three sentences. Used in the footer and on the about page.',
                'default' => "We move sea, air and road freight for importers and exporters, and handle the customs work, warehousing and final delivery that goes with it.\n\nEvery booking is handled by a named coordinator, so there is always someone who knows the file.",
                'rows' => 4,
            ],
            'company.logo' => [
                'group' => 'company', 'type' => self::TYPE_IMAGE, 'label' => 'Logo',
                'help' => 'Optional. PNG or SVG with a transparent background works best. If empty, the company name is used.',
                'default' => null,
            ],

            // Contact details and opening hours.
            'contact.email' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'General email',
                'default' => config('portlane.company.email'),
            ],
            'contact.phone' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Telephone',
                'default' => config('portlane.company.phone'),
            ],
            'contact.address_line_1' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Address line 1',
                'default' => config('portlane.company.address_line_1'),
            ],
            'contact.address_line_2' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Address line 2',
                'default' => config('portlane.company.address_line_2'),
            ],
            'contact.city' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'City',
                'default' => config('portlane.company.city'),
            ],
            'contact.region' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Region / state',
                'default' => config('portlane.company.region'),
            ],
            'contact.postal_code' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Postal code',
                'default' => config('portlane.company.postal_code'),
            ],
            'contact.country' => [
                'group' => 'contact', 'type' => self::TYPE_STRING, 'label' => 'Country',
                'default' => config('portlane.company.country'),
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
            'contact.response_note' => [
                'group' => 'contact', 'type' => self::TYPE_TEXT, 'label' => 'Contact form note',
                'default' => 'Send us the details of your shipment and a coordinator will come back to you during business hours.',
                'rows' => 3,
            ],

            // Homepage copy.
            'home.hero_eyebrow' => [
                'group' => 'home', 'type' => self::TYPE_STRING, 'label' => 'Hero eyebrow',
                'default' => 'Freight forwarding and customs brokerage',
            ],
            'home.hero_heading' => [
                'group' => 'home', 'type' => self::TYPE_STRING, 'label' => 'Hero heading',
                'default' => 'Reliable freight handling from origin to destination',
            ],
            'home.hero_intro' => [
                'group' => 'home', 'type' => self::TYPE_TEXT, 'label' => 'Hero paragraph',
                'default' => 'We arrange sea and air freight, clear customs and deliver to the door. You get one point of contact and a tracking record you can check at any time.',
                'rows' => 3,
            ],
            'home.hero_image' => [
                'group' => 'home', 'type' => self::TYPE_IMAGE, 'label' => 'Hero photograph',
                'help' => 'Optional. A wide photograph of your own operation, port or fleet. Leave empty to use the built in harbour artwork.',
                'default' => null,
            ],
            'home.services_heading' => [
                'group' => 'home', 'type' => self::TYPE_STRING, 'label' => 'Services section heading',
                'default' => 'What we handle',
            ],
            'home.services_intro' => [
                'group' => 'home', 'type' => self::TYPE_TEXT, 'label' => 'Services section intro',
                'default' => 'Five core services, run by the same team, so nothing is handed between agents at the border.',
                'rows' => 2,
            ],
            'home.why_heading' => [
                'group' => 'home', 'type' => self::TYPE_STRING, 'label' => 'Why clients choose us, heading',
                'default' => 'Why clients stay with us',
            ],
            'home.why_points' => [
                'group' => 'home', 'type' => self::TYPE_JSON, 'label' => 'Why clients choose us, points',
                'help' => 'One point per line, written as "Title | Explanation".',
                'default' => [
                    ['title' => 'One coordinator per file', 'body' => 'The person who books your shipment is the person who answers when you call about it.'],
                    ['title' => 'Documents prepared early', 'body' => 'Customs paperwork is checked before the cargo moves, which is where most delays are avoided.'],
                    ['title' => 'Tracking that is kept current', 'body' => 'Milestones are entered by the team handling the cargo, not generated automatically.'],
                    ['title' => 'Straight answers on timing', 'body' => 'If a vessel rolls or a port is congested, we tell you what it means for your delivery date.'],
                ],
            ],
            'home.destinations_heading' => [
                'group' => 'home', 'type' => self::TYPE_STRING, 'label' => 'Destinations heading',
                'default' => 'Where we ship',
            ],
            'home.destinations_intro' => [
                'group' => 'home', 'type' => self::TYPE_TEXT, 'label' => 'Destinations intro',
                'default' => 'Regular consolidations on the lanes below, and one-off bookings anywhere our carrier partners sail or fly.',
                'rows' => 2,
            ],
            'home.destinations' => [
                'group' => 'home', 'type' => self::TYPE_JSON, 'label' => 'Destination lanes',
                'help' => 'One region per line, written as "Region | Ports and airports served".',
                'default' => [
                    ['title' => 'West Africa', 'body' => 'Lagos (Apapa, Tin Can), Tema, Abidjan, Cotonou, Douala'],
                    ['title' => 'Europe', 'body' => 'Rotterdam, Antwerp, Felixstowe, Hamburg, Le Havre'],
                    ['title' => 'Middle East and Asia', 'body' => 'Jebel Ali, Shanghai, Ningbo, Guangzhou, Mumbai (Nhava Sheva)'],
                    ['title' => 'North America', 'body' => 'New York, Savannah, Houston, Montreal'],
                ],
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

            // Tracking page.
            'tracking.prefix' => [
                'group' => 'tracking', 'type' => self::TYPE_STRING, 'label' => 'Tracking number prefix',
                'help' => 'Used when new tracking numbers are generated, for example PLS-48291735. Existing numbers are not changed.',
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

            // Notifications.
            'notifications.enabled' => [
                'group' => 'notifications', 'type' => self::TYPE_BOOLEAN, 'label' => 'Send shipment update emails to customers',
                'help' => 'Emails are only sent for statuses that have notifications switched on, and only when the shipment has an email address.',
                'default' => false,
            ],
            'notifications.admin_email' => [
                'group' => 'notifications', 'type' => self::TYPE_STRING, 'label' => 'Internal notification address',
                'help' => 'Quote requests, contact messages and new customer chats are copied to this address.',
                'default' => config('portlane.notifications.admin_email'),
            ],
            'notifications.signature' => [
                'group' => 'notifications', 'type' => self::TYPE_TEXT, 'label' => 'Email sign off',
                'default' => 'Operations desk',
                'rows' => 2,
            ],

            // Search engines and social cards.
            'seo.meta_description' => [
                'group' => 'seo', 'type' => self::TYPE_TEXT, 'label' => 'Default meta description',
                'default' => 'Sea freight, air freight, customs clearance, warehousing and door to door delivery, with shipment tracking for every booking.',
                'rows' => 3,
            ],
            'seo.og_image' => [
                'group' => 'seo', 'type' => self::TYPE_IMAGE, 'label' => 'Social sharing image',
                'help' => 'Shown when a link to the site is posted on social platforms. 1200 x 630 pixels.',
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
        ];
    }

    /** @return array<string, string> */
    public static function groups(): array
    {
        return [
            'company' => 'Company',
            'contact' => 'Contact and hours',
            'home' => 'Homepage',
            'tracking' => 'Tracking',
            'notifications' => 'Notifications',
            'seo' => 'Search and social',
        ];
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return collect(self::all())->map(fn (array $definition) => $definition['default'])->all();
    }

    /** @return array{group: string, type: string, label: string, help?: string, default: mixed, rows?: int}|null */
    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}

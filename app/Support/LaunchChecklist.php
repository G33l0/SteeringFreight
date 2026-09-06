<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Review;
use App\Models\Shipment;

/**
 * What a fresh installation still needs before it is put in front of customers.
 *
 * The website deliberately ships without invented contact details, operating
 * lanes or registration numbers, so this list is how an administrator sees what
 * is still missing rather than discovering a placeholder in production.
 */
class LaunchChecklist
{
    /**
     * @return list<array{label: string, help: string, done: bool, url: string|null}>
     */
    public static function items(): array
    {
        $settings = app(Settings::class);

        $hasAddress = filled($settings->string('contact.address_line_1'))
            && filled($settings->string('contact.city'))
            && filled($settings->string('contact.country'));

        return [
            [
                'label' => 'Publish your contact details',
                'help' => 'Address, telephone and email. The footer and contact page hide these sections until they are filled in.',
                'done' => $hasAddress
                    && filled($settings->string('contact.phone'))
                    && filled($settings->string('contact.email')),
                'url' => route('admin.settings.edit', 'contact'),
            ],
            [
                'label' => 'Set the registered company name',
                'help' => 'Used in the footer copyright line and in the legal pages.',
                'done' => filled($settings->string('company.legal_name')),
                'url' => route('admin.settings.edit', 'company'),
            ],
            [
                'label' => 'Add the routes you actually operate',
                'help' => 'The homepage lanes section stays hidden until you add them, so the site never advertises a route you do not run.',
                'done' => filled($settings->list('home.destinations')),
                'url' => route('admin.settings.edit', 'home'),
            ],
            [
                'label' => 'Configure email and switch on customer updates',
                'help' => 'Add your SMTP details in the .env file, set the internal notification address, then turn shipment update emails on.',
                'done' => $settings->bool('notifications.enabled')
                    && filter_var($settings->string('notifications.admin_email'), FILTER_VALIDATE_EMAIL) !== false,
                'url' => route('admin.settings.edit', 'notifications'),
            ],
            [
                'label' => 'Complete the legal details',
                'help' => 'Governing jurisdiction, trading conditions and retention period feed straight into the privacy policy and terms.',
                'done' => filled($settings->string('legal.jurisdiction')),
                'url' => route('admin.settings.edit', 'legal'),
            ],
            [
                'label' => 'Have the legal pages reviewed',
                'help' => 'The privacy policy and terms of service are plain-language drafts. Have your own adviser review them, then tick the box in the legal settings.',
                'done' => $settings->bool('legal.reviewed'),
                'url' => route('admin.settings.edit', 'legal'),
            ],
            [
                'label' => 'Remove the sample data',
                'help' => 'Run php artisan portlane:clear-demo-data once you no longer need the demonstration shipments, customers and reviews.',
                'done' => ! self::hasSampleData(),
                'url' => route('admin.shipments.index'),
            ],
            [
                'label' => 'Turn debug mode off',
                'help' => 'Set APP_DEBUG=false and APP_ENV=production in the .env file before the site is public.',
                'done' => ! config('app.debug'),
                'url' => null,
            ],
        ];
    }

    public static function hasSampleData(): bool
    {
        return Shipment::where('is_sample', true)->exists()
            || Customer::where('is_sample', true)->exists()
            || Review::where('is_sample', true)->exists();
    }

    public static function outstanding(): int
    {
        return count(array_filter(self::items(), fn (array $item) => ! $item['done']));
    }
}

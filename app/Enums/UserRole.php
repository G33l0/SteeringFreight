<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Manager = 'manager';
    case Agent = 'agent';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Manager => 'Operations Manager',
            self::Agent => 'Freight Agent',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Administrator => 'Full access, including admin users, site settings and audit logs.',
            self::Manager => 'Manages shipments, customers, website content and customer messages.',
            self::Agent => 'Handles day to day shipment updates, messages and enquiries.',
        };
    }

    /**
     * Abilities granted to the role. Gate checks are resolved against this map,
     * so adding a granular permission table later only requires changing the
     * lookup inside User::hasPermission().
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Administrator => ['*'],
            self::Manager => [
                'shipments.view', 'shipments.manage', 'shipments.archive',
                'customers.view', 'customers.manage',
                'statuses.view', 'statuses.manage',
                'documents.view', 'documents.manage',
                'chat.view', 'chat.reply', 'chat.manage',
                'quotes.view', 'quotes.manage',
                'contact.view', 'contact.manage',
                'reviews.view', 'reviews.manage',
                'services.view', 'services.manage',
                'pages.view', 'pages.manage',
                'faqs.view', 'faqs.manage',
            ],
            self::Agent => [
                'shipments.view', 'shipments.manage',
                'customers.view', 'customers.manage',
                'documents.view', 'documents.manage',
                'chat.view', 'chat.reply',
                'quotes.view', 'quotes.manage',
                'contact.view', 'contact.manage',
            ],
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}

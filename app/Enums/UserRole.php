<?php

namespace App\Enums;

/**
 * Staff roles.
 *
 * There are two, and the split is deliberate:
 *
 * - The master admin runs the business: shipments, tracking events, statuses,
 *   documents, customers, website content, settings and staff accounts.
 * - A customer representative only answers customers. They see the conversations
 *   they have been given, together with the tracking information for the
 *   shipment each conversation is about, and nothing else.
 *
 * To add another role later, add a case here with its own permission list. The
 * gates in AppServiceProvider and the policies resolve against this map, so
 * nothing else has to change.
 */
enum UserRole: string
{
    case Administrator = 'administrator';
    case Representative = 'representative';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Master Admin',
            self::Representative => 'Customer Representative',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Administrator => 'Master Admin',
            self::Representative => 'Representative',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Administrator => 'Full access: shipments, tracking updates, statuses, documents, customers, website content, settings, staff accounts and audit logs.',
            self::Representative => 'Customer messages only. Replies to the conversations assigned to them and sees the tracking details of the shipment each one is about. Cannot create or change shipments.',
        };
    }

    /**
     * Abilities granted to the role.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Administrator => ['*'],
            self::Representative => [
                // Answering customers, and nothing else. Which conversations
                // they may open is decided by ChatConversationPolicy.
                'chat.view',
                'chat.reply',
            ],
        };
    }

    public function isRepresentative(): bool
    {
        return $this === self::Representative;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}

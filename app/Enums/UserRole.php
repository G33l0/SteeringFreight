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
            self::Representative => 'Answers customer messages, and raises and updates tracking numbers within an allowance the administrator sets. Sees only the shipments they raised themselves or were handed. Cannot archive a shipment, reach customer records, settings, staff accounts or the audit log.',
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
                // Answering customers. Which conversations they may open is
                // decided by ChatConversationPolicy.
                'chat.view',
                'chat.reply',

                // And raising and updating tracking, within an allowance. These
                // gates only open the screens; ShipmentPolicy decides, for each
                // individual shipment, whether this representative may touch
                // it, and whether they have any allowance left to create
                // another. Nothing here lets them archive a shipment, see a
                // customer record, or reach settings, staff accounts or the
                // audit log.
                'shipments.view',
                'shipments.manage',
                'statuses.view',
            ],
        };
    }

    public function isRepresentative(): bool
    {
        return $this === self::Representative;
    }

    /**
     * Whether signing in to this role needs a one time code as well as a
     * password. A master admin can read every customer record on the system,
     * which is what makes the extra step worth asking for.
     */
    public function requiresTwoFactor(): bool
    {
        return $this === self::Administrator;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}

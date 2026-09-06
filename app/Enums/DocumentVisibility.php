<?php

namespace App\Enums;

enum DocumentVisibility: string
{
    /** Downloadable by the customer from the tracking page. */
    case Customer = 'customer';

    /** Only ever visible to signed in staff. */
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Visible to the customer',
            self::Internal => 'Internal only',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}

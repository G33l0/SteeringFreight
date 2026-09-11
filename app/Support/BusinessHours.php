<?php

namespace App\Support;

/**
 * How the opening hours are worded on the public site.
 *
 * The three day settings stay editable, because a business that keeps office
 * hours should be able to say so. But a desk covered around the clock should
 * not be described as three identical rows in a table, so when the settings all
 * say the same thing the site says it once instead.
 */
class BusinessHours
{
    /**
     * True when the three day settings agree, which is what a desk that never
     * closes looks like once it has been filled in.
     */
    public static function isAlwaysOpen(): bool
    {
        $values = collect(self::raw())
            ->map(fn (string $value) => mb_strtolower(trim($value)))
            ->filter();

        return $values->count() === 3 && $values->unique()->count() === 1;
    }

    /**
     * The rows to print in the opening hours list: one when the desk is always
     * open, otherwise the three days.
     *
     * @return array<string, string>
     */
    public static function rows(): array
    {
        [$weekdays, $saturday, $sunday] = self::raw();

        if (self::isAlwaysOpen()) {
            return ['Every day' => $weekdays];
        }

        return [
            'Monday to Friday' => $weekdays,
            'Saturday' => $saturday,
            'Sunday' => $sunday,
        ];
    }

    /**
     * The hours as a phrase that can be dropped into a sentence, for the places
     * that say "our desk is open ..." rather than printing a table.
     */
    public static function sentence(): string
    {
        [$weekdays] = self::raw();

        return self::isAlwaysOpen()
            ? 'every day, around the clock'
            : $weekdays.' on weekdays';
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private static function raw(): array
    {
        $settings = app(Settings::class);

        return [
            $settings->string('contact.hours_weekdays'),
            $settings->string('contact.hours_saturday'),
            $settings->string('contact.hours_sunday'),
        ];
    }
}

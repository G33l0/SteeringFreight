<?php

namespace App\Support;

/**
 * Placeholders an administrator can use in page copy, resolved from the site
 * settings when the page is rendered.
 *
 * Writing [[legal.jurisdiction]] in the terms of service keeps the document in
 * step with the settings screen. A line containing a placeholder that has no
 * value yet is left out of the rendered page entirely, so a page never shows
 * "registered in ______" while the detail is still unknown.
 */
class ContentTokens
{
    /**
     * @return array<string, string|null>
     */
    public static function values(): array
    {
        $settings = app(Settings::class);

        $address = collect([
            $settings->string('contact.address_line_1'),
            $settings->string('contact.address_line_2'),
            collect([
                $settings->string('contact.city'),
                $settings->string('contact.region'),
                $settings->string('contact.postal_code'),
            ])->filter()->implode(', '),
            $settings->string('contact.country'),
        ])->filter()->implode(', ');

        return [
            'company.name' => company_name(),
            'company.legal_name' => $settings->string('company.legal_name') ?: company_name(),
            'company.registration_number' => $settings->string('company.registration_number') ?: null,
            'contact.email' => $settings->string('contact.email') ?: null,
            'contact.operations_email' => $settings->string('contact.operations_email')
                ?: ($settings->string('contact.email') ?: null),
            'contact.phone' => $settings->string('contact.phone') ?: null,
            'contact.address' => $address ?: null,
            'legal.jurisdiction' => $settings->string('legal.jurisdiction') ?: null,
            'legal.trading_conditions' => $settings->string('legal.trading_conditions') ?: null,
            'legal.retention_period' => $settings->string('legal.retention_period') ?: null,
            'legal.contact_email' => $settings->string('legal.contact_email')
                ?: ($settings->string('contact.email') ?: null),
        ];
    }

    /**
     * Replace placeholders, dropping any line whose placeholder has no value.
     */
    public static function apply(string $content): string
    {
        if (! str_contains($content, '[[')) {
            return $content;
        }

        $values = self::values();
        $lines = [];

        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            $skip = false;

            $resolved = preg_replace_callback('/\[\[([a-z_]+\.[a-z_]+)\]\]/i', function (array $matches) use ($values, &$skip): string {
                $value = $values[$matches[1]] ?? null;

                if ($value === null || $value === '') {
                    $skip = true;

                    return '';
                }

                return (string) $value;
            }, $line);

            if (! $skip) {
                $lines[] = $resolved;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * The placeholders available, for the help text on the page editor.
     *
     * @return list<string>
     */
    public static function available(): array
    {
        return array_keys(self::values());
    }
}

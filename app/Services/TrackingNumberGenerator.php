<?php

namespace App\Services;

use App\Models\Shipment;
use App\Support\Settings;
use RuntimeException;

/**
 * Builds unique tracking numbers in the form PREFIX-NNNNNNNN.
 *
 * The prefix and digit count come from the site settings, so the format can be
 * changed without a code change. Numbers already issued are never rewritten.
 */
class TrackingNumberGenerator
{
    private const MAX_ATTEMPTS = 25;

    public function __construct(private readonly Settings $settings) {}

    public function generate(): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $candidate = $this->format($this->randomDigits());

            if (! Shipment::where('tracking_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException('Unable to generate a unique tracking number. Increase the number of digits in the tracking settings.');
    }

    public function prefix(): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $this->settings->string('tracking.prefix')) ?? '');

        return $prefix !== '' ? $prefix : (string) config('portlane.tracking.prefix');
    }

    public function digits(): int
    {
        $digits = $this->settings->int('tracking.digits', (int) config('portlane.tracking.digits'));

        return max(4, min(14, $digits));
    }

    public function example(): string
    {
        return $this->format(str_pad('48291735', $this->digits(), '0'));
    }

    /**
     * Accepts what a customer typed and returns the canonical form:
     * upper case, no spaces, and the separator inserted after the prefix.
     */
    public function normalise(string $value): string
    {
        $value = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $value) ?? '');

        $prefix = $this->prefix();

        if ($prefix !== '' && str_starts_with($value, $prefix)) {
            return $this->format(substr($value, strlen($prefix)));
        }

        return $value;
    }

    private function format(string $digits): string
    {
        return $this->prefix().config('portlane.tracking.separator', '-').$digits;
    }

    private function randomDigits(): string
    {
        $digits = '';

        for ($i = 0; $i < $this->digits(); $i++) {
            $digits .= (string) random_int($i === 0 ? 1 : 0, 9);
        }

        return $digits;
    }
}

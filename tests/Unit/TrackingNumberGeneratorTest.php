<?php

namespace Tests\Unit;

use App\Services\TrackingNumberGenerator;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_configured_prefix_and_length(): void
    {
        $settings = app(Settings::class);
        $settings->set('tracking.prefix', 'HRB');
        $settings->set('tracking.digits', 10);

        $number = app(TrackingNumberGenerator::class)->generate();

        $this->assertMatchesRegularExpression('/^HRB-\d{10}$/', $number);
    }

    public function test_it_normalises_what_a_customer_types(): void
    {
        $generator = app(TrackingNumberGenerator::class);
        $prefix = $generator->prefix();

        $this->assertSame("{$prefix}-48291735", $generator->normalise(strtolower("{$prefix} 48291735")));
        $this->assertSame("{$prefix}-48291735", $generator->normalise("{$prefix}48291735"));
        $this->assertSame("{$prefix}-48291735", $generator->normalise("  {$prefix}-48291735  "));
    }

    public function test_the_example_number_follows_the_configured_format(): void
    {
        app(Settings::class)->set('tracking.prefix', 'PLS');

        $this->assertStringStartsWith('PLS-', app(TrackingNumberGenerator::class)->example());
    }
}

<?php

namespace Tests\Unit;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_falls_back_to_the_defined_default(): void
    {
        $this->assertNotEmpty(app(Settings::class)->string('company.tagline'));
    }

    public function test_a_saved_value_replaces_the_default(): void
    {
        $settings = app(Settings::class);
        $settings->set('company.name', 'Harbourline Freight');

        $this->assertSame('Harbourline Freight', $settings->string('company.name'));
        $this->assertSame('Harbourline Freight', company_name());
    }

    public function test_booleans_and_lists_are_cast_back_correctly(): void
    {
        $settings = app(Settings::class);

        $settings->set('notifications.enabled', true);
        $this->assertTrue($settings->bool('notifications.enabled'));

        $settings->set('notifications.enabled', false);
        $this->assertFalse($settings->bool('notifications.enabled'));

        $settings->set('home.why_points', [['title' => 'One point', 'body' => 'Explanation']]);
        $this->assertSame('One point', $settings->list('home.why_points')[0]['title']);
    }
}

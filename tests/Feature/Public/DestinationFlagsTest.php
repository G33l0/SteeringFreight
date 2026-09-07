<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestinationFlagsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    public function test_the_destinations_section_is_hidden_until_lanes_are_added(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(setting('home.destinations_heading'));
    }

    public function test_each_country_in_a_lane_is_shown_with_its_flag(): void
    {
        settings()->set('home.destinations', [
            ['title' => 'West Africa', 'body' => 'Nigeria, Ghana, Benin'],
        ]);

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee(setting('home.destinations_heading'));
        $response->assertSee('🇳🇬', false);
        $response->assertSee('🇬🇭', false);
        $response->assertSee('🇧🇯', false);
        $response->assertSee('Flag of Nigeria', false);

        // The name is always printed next to the flag, never replaced by it.
        $response->assertSee('Nigeria');
    }

    public function test_a_lane_titled_with_a_country_carries_that_flag(): void
    {
        settings()->set('home.destinations', [
            ['title' => 'China', 'body' => 'Shanghai, Ningbo, Shenzhen'],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('🇨🇳', false)
            ->assertSee('Shanghai, Ningbo, Shenzhen');
    }

    public function test_copy_that_is_not_a_country_list_is_printed_as_written(): void
    {
        settings()->set('home.destinations', [
            ['title' => 'Inland Europe', 'body' => 'Road groupage from the Rotterdam and Antwerp gateways, twice weekly.'],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Road groupage from the Rotterdam and Antwerp gateways, twice weekly.')
            ->assertDontSee('Flag of', false);
    }
}

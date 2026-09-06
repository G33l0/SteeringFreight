<?php

namespace Tests\Feature\Admin;

use App\Models\Review;
use App\Models\Shipment;
use App\Support\LaunchChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaunchChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    public function test_a_fresh_installation_lists_what_is_still_missing(): void
    {
        $this->assertGreaterThan(0, LaunchChecklist::outstanding());

        $this->actingAs($this->administrator())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Before you go live')
            ->assertSee('Publish your contact details')
            ->assertSee('Add the routes you actually operate');
    }

    public function test_completing_a_setting_ticks_its_item_off(): void
    {
        $before = LaunchChecklist::outstanding();

        settings()->setMany([
            'contact.address_line_1' => 'Unit 4, Dock Road',
            'contact.city' => 'Felixstowe',
            'contact.country' => 'United Kingdom',
            'contact.phone' => '+44 20 0000 0000',
            'contact.email' => 'operations@portlane.test',
        ]);

        $this->assertSame($before - 1, LaunchChecklist::outstanding());
    }

    public function test_sample_data_is_reported_until_it_is_cleared(): void
    {
        Shipment::factory()->sample()->create();
        Review::factory()->create(['is_sample' => true]);

        $this->assertTrue(LaunchChecklist::hasSampleData());

        $this->artisan('portlane:clear-demo-data', ['--force' => true])->assertSuccessful();

        $this->assertFalse(LaunchChecklist::hasSampleData());
    }

    public function test_a_representative_does_not_see_the_checklist(): void
    {
        $this->actingAs($this->representative())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('My conversations')
            ->assertDontSee('Before you go live');
    }
}

<?php

namespace Tests\Feature\Public;

use App\Models\Page;
use App\Models\Service;
use App\Support\ContentFormatter;
use Database\Seeders\PageSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    public function test_the_built_in_logo_and_social_card_are_used_until_a_logo_is_uploaded(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('assets/brand/portlane-og.jpg', false)
            ->assertSee('favicon.svg', false);
    }

    public function test_brand_colours_are_only_published_when_they_differ_from_the_default(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('--color-accent-600:', false);

        settings()->set('brand.accent_colour', '#1d6f42');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('--color-accent-600: #1d6f42', false);
    }

    public function test_a_service_without_a_photograph_falls_back_to_the_bundled_illustration(): void
    {
        $this->seed(ServiceSeeder::class);

        // A service added after the application shipped has no photograph.
        $service = Service::factory()->create(['slug' => 'project-cargo', 'is_published' => true]);

        $this->assertStringContainsString('assets/illustrations/cargo-handling.svg', $service->imageUrl());
        $this->assertFalse($service->hasUploadedImage());

        $this->get(route('services.show', $service))
            ->assertOk()
            ->assertSee('assets/illustrations/cargo-handling.svg', false);
    }

    public function test_an_unknown_service_slug_still_gets_artwork(): void
    {
        $service = Service::factory()->create(['slug' => 'project-cargo']);

        $this->assertStringContainsString('assets/illustrations/cargo-handling.svg', $service->imageUrl());
    }

    public function test_legal_placeholders_are_replaced_from_the_settings(): void
    {
        settings()->set('legal.jurisdiction', 'England and Wales');

        $html = ContentFormatter::render('These terms are governed by the law of [[legal.jurisdiction]].')->toHtml();

        $this->assertStringContainsString('governed by the law of England and Wales.', $html);
        $this->assertStringNotContainsString('[[', $html);
    }

    public function test_a_line_with_an_unset_placeholder_is_left_out_entirely(): void
    {
        $html = ContentFormatter::render("First line stays.\n[[company.registration_number]] is not known yet.")->toHtml();

        $this->assertStringContainsString('First line stays.', $html);
        $this->assertStringNotContainsString('is not known yet', $html);
        $this->assertStringNotContainsString('[[', $html);
    }

    public function test_the_seeded_terms_page_never_shows_an_empty_placeholder(): void
    {
        $this->seed(PageSeeder::class);

        $response = $this->get(route('terms'));

        $response->assertOk()->assertDontSee('[[', false);
        $response->assertDontSee('registration number');

        settings()->set('company.registration_number', 'RC-000000');
        settings()->set('company.legal_name', 'Portlane Shipping Limited');

        $this->get(route('terms'))->assertOk()->assertSee('RC-000000');
    }

    public function test_contact_details_are_not_published_until_they_are_entered(): void
    {
        $this->get(route('contact.create'))
            ->assertOk()
            ->assertSee('Our published telephone and');

        settings()->setMany([
            'contact.phone' => '+44 20 0000 0000',
            'contact.email' => 'operations@portlane.test',
            'contact.address_line_1' => 'Unit 4, Dock Road',
            'contact.city' => 'Felixstowe',
            'contact.country' => 'United Kingdom',
        ]);

        $this->get(route('contact.create'))
            ->assertOk()
            ->assertSee('Unit 4, Dock Road')
            ->assertSee('+44 20 0000 0000')
            ->assertDontSee('Our published telephone and');
    }

    public function test_the_homepage_lane_section_stays_hidden_until_routes_are_added(): void
    {
        $page = Page::factory()->create();
        $this->assertNotNull($page);

        $this->get(route('home'))->assertOk()->assertDontSee('Where we ship');

        settings()->set('home.destinations', [['title' => 'West Africa', 'body' => 'Lagos, Tema']]);

        $this->get(route('home'))->assertOk()->assertSee('Where we ship')->assertSee('Lagos, Tema');
    }
}

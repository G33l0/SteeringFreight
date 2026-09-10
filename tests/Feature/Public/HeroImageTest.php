<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    public function test_the_bundled_photograph_is_used_until_one_is_uploaded(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('assets/photos/hero.webp', false);
    }

    public function test_the_bundled_photograph_exists(): void
    {
        // The homepage has no artwork to fall back on any more, so a missing file
        // would be a broken hero rather than a degraded one.
        $this->assertFileExists(public_path('assets/photos/hero.webp'));
    }

    public function test_an_uploaded_photograph_replaces_it(): void
    {
        settings()->set('home.hero_image', 'site/quayside.jpg');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('site/quayside.jpg', false)
            ->assertDontSee('assets/photos/hero.webp', false);
    }

    public function test_the_about_page_uses_the_bundled_banner_until_one_is_uploaded(): void
    {
        $this->assertFileExists(public_path('assets/photos/about-banner.webp'));

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('assets/photos/about-banner.webp', false);
    }

    public function test_an_uploaded_about_banner_replaces_it(): void
    {
        settings()->set('company.about_image', 'site/our-terminal.jpg');

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('site/our-terminal.jpg', false)
            ->assertDontSee('assets/photos/about-banner.webp', false);
    }

    public function test_the_social_card_ships_with_the_application(): void
    {
        $this->assertFileExists(public_path('assets/brand/portlane-og.jpg'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('assets/brand/portlane-og.jpg', false);
    }
}

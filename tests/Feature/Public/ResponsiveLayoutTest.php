<?php

namespace Tests\Feature\Public;

use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The public pages share one page margin and two vertical steps, both of which
 * grow with the viewport rather than jumping at a breakpoint. A page that
 * invents its own spacing shows up here, as does a menu that would be painted
 * open on a slow connection or a control with no accessible name.
 */
class ResponsiveLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    #[DataProvider('publicPages')]
    public function test_every_public_page_uses_the_shared_page_margin(string $route): void
    {
        $response = $this->get(route($route));

        $response->assertOk()
            ->assertSee('px-gutter', false)
            ->assertDontSee('class="mx-auto max-w-6xl px-6', false);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function publicPages(): array
    {
        return [
            'home' => ['home'],
            'about' => ['about'],
            'services' => ['services.index'],
            'quote' => ['quote.create'],
            'contact' => ['contact.create'],
            'faq' => ['faq'],
            'reviews' => ['reviews'],
            'tracking' => ['track.index'],
        ];
    }

    public function test_the_homepage_headings_scale_with_the_viewport(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('text-display', false)
            ->assertSee('text-title', false)
            ->assertSee('py-hero', false)
            ->assertSee('py-section', false);
    }

    public function test_the_header_stays_with_the_page_and_carries_the_menu(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('class="sticky top-0 z-40"', false)
            ->assertSee('aria-controls="mobile-nav"', false)
            ->assertSee('aria-label="Toggle navigation"', false)
            ->assertSee('aria-label="Track a shipment"', false);
    }

    public function test_a_menu_is_hidden_until_alpine_has_booted(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        // Both collapsible menus in the header: the services dropdown and the
        // mobile navigation. Without x-cloak each is open until the JavaScript
        // arrives, which on a phone means a page that starts covered.
        $this->assertSame(
            2,
            preg_match_all('/x-show="(services|open)"[^>]*x-cloak/', (string) $html),
        );
    }

    public function test_the_shipment_table_scrolls_rather_than_stretching_the_page(): void
    {
        Shipment::factory()->create(['shipment_status_id' => $this->trackingStatus('in-transit')->id]);

        $this->actingAs($this->administrator())
            ->get(route('admin.shipments.index'))
            ->assertOk()
            ->assertSee('class="table-scroll"', false);
    }
}

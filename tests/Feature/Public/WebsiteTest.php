<?php

namespace Tests\Feature\Public;

use App\Models\Page;
use App\Models\Service;
use Database\Seeders\FaqSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        $this->seed(ServiceSeeder::class);
        $this->seed(PageSeeder::class);
        $this->seed(FaqSeeder::class);
    }

    public function test_the_main_pages_load(): void
    {
        foreach ([
            route('home'),
            route('about'),
            route('services.index'),
            route('track.index'),
            route('quote.create'),
            route('contact.create'),
            route('faq'),
            route('reviews'),
            route('privacy'),
            route('terms'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_every_published_service_has_a_page(): void
    {
        foreach (Service::published()->get() as $service) {
            $this->get(route('services.show', $service))
                ->assertOk()
                ->assertSee($service->title);
        }
    }

    public function test_an_unpublished_service_is_not_reachable(): void
    {
        $service = Service::factory()->unpublished()->create();

        $this->get(route('services.show', $service))->assertNotFound();
    }

    public function test_the_homepage_shows_the_company_name_from_settings(): void
    {
        settings()->set('company.name', 'Harbourline Freight');

        $this->get(route('home'))->assertOk()->assertSee('Harbourline Freight');
    }

    public function test_the_sitemap_and_robots_file_are_generated(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('track.index'), false);

        $this->get(route('robots'))
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee(route('sitemap'), false);
    }

    public function test_missing_pages_render_the_error_template(): void
    {
        $this->get('/a-page-that-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found');
    }

    public function test_page_content_is_escaped_rather_than_rendered_as_html(): void
    {
        $page = Page::factory()->create([
            'body' => '<script>alert("xss")</script> Ordinary paragraph text.',
        ]);

        $this->get(route('pages.show', $page))
            ->assertOk()
            ->assertSee('Ordinary paragraph text.')
            ->assertDontSee('<script>alert', false);
    }
}

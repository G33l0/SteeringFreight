<?php

namespace Tests\Feature\Public;

use App\Models\Service;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Service pictures come from three places, in order: a photograph uploaded by
 * staff, a photograph bundled with the application, then the drawn artwork.
 * Photographs are added one service at a time, so the two must coexist.
 */
class ServiceImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        $this->seed(ServiceSeeder::class);
    }

    /**
     * A service added after the application shipped, which therefore has no
     * photograph bundled for it.
     */
    private function serviceWithoutPhotograph(): Service
    {
        return Service::factory()->create([
            'title' => 'Project Cargo',
            'slug' => 'project-cargo',
            'is_published' => true,
        ]);
    }

    public function test_a_service_with_a_bundled_photograph_uses_it_instead_of_the_artwork(): void
    {
        $service = Service::where('slug', 'sea-freight')->firstOrFail();

        $this->assertFileExists(public_path('assets/photos/service-sea-freight.webp'));
        $this->assertStringContainsString('assets/photos/service-sea-freight.webp', $service->imageUrl());
        $this->assertStringNotContainsString('illustrations', $service->imageUrl());
    }

    public function test_a_service_added_later_gets_the_generic_artwork(): void
    {
        $service = $this->serviceWithoutPhotograph();

        $this->assertNull($service->bundledPhotoPath());
        $this->assertStringContainsString('assets/illustrations/cargo-handling.svg', $service->imageUrl());
    }

    public function test_an_uploaded_photograph_wins_over_the_bundled_one(): void
    {
        $service = Service::where('slug', 'sea-freight')->firstOrFail();
        $service->update(['image_path' => 'services/our-own-quay.jpg']);

        $this->assertStringContainsString('our-own-quay.jpg', $service->fresh()->imageUrl());
    }

    public function test_a_bundled_photograph_is_described_rather_than_just_named(): void
    {
        $service = Service::where('slug', 'sea-freight')->firstOrFail();

        // Alt text for a photograph should say what is in it.
        $this->assertStringContainsString('gantry crane', $service->imageAlt());

        // Generic artwork is named by its service, which is all it can be.
        $drawn = $this->serviceWithoutPhotograph();
        $this->assertSame($drawn->title, $drawn->imageAlt());
    }

    public function test_alt_text_set_by_staff_is_always_respected(): void
    {
        $service = Service::where('slug', 'sea-freight')->firstOrFail();
        $service->update(['image_alt' => 'Our own container being loaded at Apapa']);

        $this->assertSame('Our own container being loaded at Apapa', $service->fresh()->imageAlt());
    }

    public function test_every_service_the_application_ships_with_has_a_photograph(): void
    {
        $response = $this->get(route('services.index'))->assertOk();

        foreach (Service::published()->get() as $service) {
            $photo = $service->bundledPhotoPath();

            $this->assertNotNull($photo, "{$service->title} has no bundled photograph.");
            $response->assertSee($photo, false);
        }
    }
}

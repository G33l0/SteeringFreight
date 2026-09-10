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

    public function test_a_service_with_a_bundled_photograph_uses_it_instead_of_the_artwork(): void
    {
        $service = Service::where('slug', 'sea-freight')->firstOrFail();

        $this->assertFileExists(public_path('assets/photos/service-sea-freight.webp'));
        $this->assertStringContainsString('assets/photos/service-sea-freight.webp', $service->imageUrl());
        $this->assertStringNotContainsString('illustrations', $service->imageUrl());
    }

    public function test_a_service_without_one_keeps_its_artwork(): void
    {
        $service = Service::where('slug', 'customs-clearance')->firstOrFail();

        $this->assertStringContainsString('assets/illustrations/customs-clearance.svg', $service->imageUrl());
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

        // Artwork is named by its service, which is all it depicts.
        $this->assertSame(
            'Customs Clearance',
            Service::where('slug', 'customs-clearance')->firstOrFail()->imageAlt(),
        );
    }

    public function test_alt_text_set_by_staff_is_always_respected(): void
    {
        $service = Service::where('slug', 'sea-freight')->firstOrFail();
        $service->update(['image_alt' => 'Our own container being loaded at Apapa']);

        $this->assertSame('Our own container being loaded at Apapa', $service->fresh()->imageAlt());
    }

    public function test_the_services_page_renders_both_kinds(): void
    {
        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee('assets/photos/service-sea-freight.webp', false)
            ->assertSee('assets/illustrations/air-freight.svg', false);
    }
}

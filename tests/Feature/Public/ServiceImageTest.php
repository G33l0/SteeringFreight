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

    public function test_every_service_renders_the_picture_it_actually_has(): void
    {
        $response = $this->get(route('services.index'))->assertOk();

        // Worked out from what is on disk rather than named here, so landing the
        // next photograph does not mean editing this test.
        $photographed = 0;
        $drawn = 0;

        foreach (Service::published()->get() as $service) {
            if ($photo = $service->bundledPhotoPath()) {
                $response->assertSee($photo, false);
                $photographed++;

                continue;
            }

            $response->assertSee("assets/illustrations/{$service->slug}.svg", false);
            $drawn++;
        }

        // Both kinds are in use while the photographs arrive one at a time. When
        // the last one lands this drops to zero, which is the moment to retire
        // the artwork rather than a failure.
        $this->assertGreaterThan(0, $photographed, 'No service is using a bundled photograph.');
        $this->assertSame(Service::published()->count(), $photographed + $drawn);
    }
}

<?php

namespace Tests\Feature\Public;

use App\Models\Review;
use App\Models\Shipment;
use App\Support\Countries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactDetailsAndReviewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    public function test_extra_contact_details_added_in_the_settings_reach_the_website(): void
    {
        settings()->set('contact.extra_details', [
            ['title' => 'WhatsApp', 'body' => '+234 000 000 0000'],
            ['title' => 'Lagos branch', 'body' => '12 Wharf Road, Apapa'],
            ['title' => 'Out of hours', 'body' => 'emergency@portlane.test'],
        ]);

        $this->get(route('contact.create'))
            ->assertOk()
            ->assertSee('WhatsApp')
            ->assertSee('+234 000 000 0000')
            ->assertSee('Lagos branch')
            ->assertSee('12 Wharf Road, Apapa');

        // The footer carries them on every page.
        $this->get(route('home'))->assertOk()->assertSee('Out of hours');
    }

    public function test_extra_contact_details_also_show_on_the_tracking_page(): void
    {
        settings()->set('contact.extra_details', [
            ['title' => 'Operations WhatsApp', 'body' => '+234 111 111 1111'],
        ]);

        $shipment = Shipment::factory()->create();

        $this->get(route('track.show', $shipment->tracking_number))
            ->assertOk()
            ->assertSee('Operations WhatsApp')
            ->assertSee('+234 111 111 1111');
    }

    public function test_nothing_is_shown_when_no_extra_details_have_been_added(): void
    {
        $this->get(route('contact.create'))->assertOk()->assertDontSee('Operations WhatsApp');
    }

    public function test_the_reviews_heading_and_intro_come_from_the_settings(): void
    {
        Review::factory()->create(['body' => 'They kept us informed the whole way.']);

        settings()->setMany([
            'home.reviews_heading' => 'In our clients words',
            'home.reviews_intro' => 'Published with permission after delivery.',
        ]);

        $this->get(route('reviews'))
            ->assertOk()
            ->assertSee('In our clients words')
            ->assertSee('Published with permission after delivery.');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('In our clients words')
            ->assertSee('They kept us informed the whole way.');
    }

    public function test_the_master_admin_can_add_a_review_that_appears_on_the_website(): void
    {
        $this->actingAs($this->administrator())->post(route('admin.reviews.store'), [
            'customer_name' => 'Ifeoma Balogun',
            'company' => 'Coastal Supplies',
            'location' => 'Port Harcourt, Nigeria',
            'rating' => 5,
            'body' => 'Paperwork was ready before the vessel arrived.',
            'is_published' => '1',
        ])->assertRedirect(route('admin.reviews.index'));

        $this->get(route('reviews'))
            ->assertOk()
            ->assertSee('Ifeoma Balogun')
            ->assertSee('Paperwork was ready before the vessel arrived.');
    }

    public function test_the_quote_page_copy_comes_from_the_settings(): void
    {
        settings()->set('quotes.intro', 'Send us the route and we will price it the same day.');

        $this->get(route('quote.create'))
            ->assertOk()
            ->assertSee('Send us the route and we will price it the same day.');
    }

    public function test_the_confirmation_message_is_editable_and_carries_the_reference(): void
    {
        settings()->set('quotes.confirmation', 'Received, thank you.');

        $this->post(route('quote.store'), [
            'name' => 'Ada Nwosu',
            'email' => 'ada@example.com',
            'origin_country' => 'China',
            'destination_country' => 'Nigeria',
            'cargo_type' => 'Kitchen equipment',
        ])->assertSessionHas('status', fn (string $status) => str_contains($status, 'Received, thank you.')
            && str_contains($status, 'QR-'));
    }

    public function test_frequently_shipped_countries_can_be_set_by_the_master_admin(): void
    {
        settings()->set('quotes.frequent_countries', [
            ['title' => 'Vietnam', 'body' => ''],
            ['title' => 'Japan', 'body' => ''],
            ['title' => 'Not A Country', 'body' => ''],
        ]);

        $frequent = Countries::frequentlyUsed();

        $this->assertSame(['Vietnam', 'Japan'], $frequent);
        $this->get(route('quote.create'))->assertOk()->assertSee('Frequently shipped');
    }
}

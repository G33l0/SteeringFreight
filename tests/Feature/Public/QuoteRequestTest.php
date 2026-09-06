<?php

namespace Tests\Feature\Public;

use App\Enums\QuoteStatus;
use App\Enums\ShippingMethod;
use App\Models\QuoteRequest;
use App\Notifications\QuoteRequestReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuoteRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        Notification::fake();
    }

    public function test_the_quote_form_is_reachable(): void
    {
        $this->get(route('quote.create'))->assertOk()->assertSee('Request a quote');
    }

    public function test_a_visitor_can_request_a_quote(): void
    {
        $response = $this->post(route('quote.store'), [
            'name' => 'Ada Nwosu',
            'company' => 'Nwosu Trading',
            'email' => 'ada@example.com',
            'phone' => '+234 000 000 0000',
            'origin_country' => 'China',
            'origin_city' => 'Shanghai',
            'destination_country' => 'Nigeria',
            'destination_city' => 'Lagos',
            'shipping_method' => ShippingMethod::SeaFreightLcl->value,
            'incoterm' => 'FOB',
            'cargo_type' => 'Kitchen equipment',
            'approximate_weight' => '850 kg',
            'dimensions' => '9 cartons, 60 x 40 x 40 cm',
            'goods_value' => 'USD 12,000',
            'package_count' => 9,
            'message' => 'Cargo will be ready in two weeks.',
        ]);

        $response->assertRedirect(route('quote.create'))->assertSessionHas('status');

        $quote = QuoteRequest::firstOrFail();

        $this->assertSame('Ada Nwosu', $quote->name);
        $this->assertSame(QuoteStatus::New, $quote->status);
        $this->assertStringStartsWith('QR-', $quote->reference);

        // The structured route also fills the readable labels.
        $this->assertSame('China', $quote->origin_country);
        $this->assertSame('Shanghai, China', $quote->origin);
        $this->assertSame('Lagos, Nigeria', $quote->destination);
        $this->assertSame('FOB', $quote->incoterm);
        $this->assertSame('USD 12,000', $quote->goods_value);

        Notification::assertSentOnDemand(QuoteRequestReceived::class);
    }

    public function test_the_quote_form_validates_its_input(): void
    {
        $this->post(route('quote.store'), ['name' => '', 'email' => 'not-an-email'])
            ->assertSessionHasErrors(['name', 'email', 'origin_country', 'destination_country', 'cargo_type']);

        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_the_country_must_come_from_the_list(): void
    {
        $this->post(route('quote.store'), [
            'name' => 'Ada Nwosu',
            'email' => 'ada@example.com',
            'origin_country' => 'Wakanda',
            'destination_country' => 'Nigeria',
            'cargo_type' => 'Kitchen equipment',
        ])->assertSessionHasErrors('origin_country');

        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_the_form_offers_countries_across_every_region(): void
    {
        $response = $this->get(route('quote.create'));

        $response->assertOk();

        foreach (['China', 'Japan', 'Singapore', 'India', 'United Arab Emirates', 'Vietnam',
            'Indonesia', 'Kazakhstan', 'Nigeria', 'Brazil', 'Germany', 'United States'] as $country) {
            $response->assertSee($country);
        }
    }

    public function test_the_honeypot_field_blocks_automated_submissions(): void
    {
        $this->post(route('quote.store'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'origin_country' => 'China',
            'destination_country' => 'Nigeria',
            'cargo_type' => 'Anything',
            'website' => 'http://spam.example.com',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_an_administrator_can_progress_a_quote(): void
    {
        $quote = QuoteRequest::factory()->create();
        $user = $this->administrator();

        $this->actingAs($user)->get(route('admin.quotes.index'))->assertOk()->assertSee($quote->reference);

        $this->actingAs($user)->put(route('admin.quotes.update', $quote), [
            'status' => QuoteStatus::Quoted->value,
            'internal_notes' => 'Rate sent, awaiting confirmation.',
        ])->assertRedirect();

        $quote->refresh();

        $this->assertSame(QuoteStatus::Quoted, $quote->status);
        $this->assertSame($user->id, $quote->handled_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'quote.updated']);
    }

    public function test_quote_submissions_are_rate_limited(): void
    {
        $payload = [
            'name' => 'Ada Nwosu',
            'email' => 'ada@example.com',
            'origin_country' => 'China',
            'destination_country' => 'Nigeria',
            'cargo_type' => 'Kitchen equipment',
        ];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('quote.store'), $payload);
        }

        $this->post(route('quote.store'), $payload)->assertStatus(429);
    }
}

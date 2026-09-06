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
            'email' => 'ada@example.com',
            'phone' => '+234 000 000 0000',
            'origin' => 'Shanghai, China',
            'destination' => 'Lagos, Nigeria',
            'shipping_method' => ShippingMethod::SeaFreightLcl->value,
            'cargo_type' => 'Kitchen equipment',
            'approximate_weight' => '850 kg',
            'package_count' => 9,
            'message' => 'Cargo will be ready in two weeks.',
        ]);

        $response->assertRedirect(route('quote.create'))->assertSessionHas('status');

        $quote = QuoteRequest::firstOrFail();

        $this->assertSame('Ada Nwosu', $quote->name);
        $this->assertSame(QuoteStatus::New, $quote->status);
        $this->assertStringStartsWith('QR-', $quote->reference);

        Notification::assertSentOnDemand(QuoteRequestReceived::class);
    }

    public function test_the_quote_form_validates_its_input(): void
    {
        $this->post(route('quote.store'), ['name' => '', 'email' => 'not-an-email'])
            ->assertSessionHasErrors(['name', 'email', 'origin', 'destination']);

        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_the_honeypot_field_blocks_automated_submissions(): void
    {
        $this->post(route('quote.store'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'origin' => 'A',
            'destination' => 'B',
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
            'origin' => 'Shanghai',
            'destination' => 'Lagos',
        ];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('quote.store'), $payload);
        }

        $this->post(route('quote.store'), $payload)->assertStatus(429);
    }
}

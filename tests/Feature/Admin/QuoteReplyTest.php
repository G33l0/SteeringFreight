<?php

namespace Tests\Feature\Admin;

use App\Enums\QuoteStatus;
use App\Models\QuoteReply;
use App\Models\QuoteRequest;
use App\Notifications\QuoteReplySent;
use App\Notifications\QuoteRequestReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuoteReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        Notification::fake();
    }

    public function test_a_new_request_reaches_the_dashboard_and_the_operations_mailbox(): void
    {
        settings()->set('notifications.admin_email', 'operations@portlane.test');

        $this->post(route('quote.store'), [
            'name' => 'Ada Nwosu',
            'email' => 'ada@example.com',
            'origin_country' => 'China',
            'origin_city' => 'Ningbo',
            'destination_country' => 'Nigeria',
            'destination_city' => 'Lagos',
            'cargo_type' => 'Packaging machinery spares',
        ])->assertRedirect();

        $quote = QuoteRequest::firstOrFail();

        // Webmail: addressed to the operations desk, replying goes to the customer.
        Notification::assertSentOnDemand(
            QuoteRequestReceived::class,
            function ($notification, $channels, $notifiable) {
                $mail = $notification->toMail($notifiable);

                return $notifiable->routes['mail'] === 'operations@portlane.test'
                    && $mail->replyTo[0][0] === 'ada@example.com';
            },
        );

        // Dashboard: it is listed, counted and openable.
        $administrator = $this->administrator();

        $this->actingAs($administrator)
            ->get(route('admin.quotes.index'))
            ->assertOk()
            ->assertSee($quote->reference)
            ->assertSee('Ningbo, China')
            ->assertSee('Awaiting a quotation');

        $this->actingAs($administrator)
            ->get(route('admin.quotes.show', $quote))
            ->assertOk()
            ->assertSee('Packaging machinery spares')
            ->assertSee('Send a quotation')
            ->assertSee('Reply from webmail');
    }

    public function test_the_master_admin_can_send_a_quotation_from_the_dashboard(): void
    {
        $administrator = $this->administrator(['name' => 'Ops Lead']);
        $quote = QuoteRequest::factory()->create(['email' => 'customer@example.com', 'name' => 'Ada Nwosu']);

        $this->actingAs($administrator)
            ->post(route('admin.quotes.reply', $quote), [
                'subject' => 'Quotation '.$quote->reference,
                'currency' => 'usd',
                'quoted_amount' => '2,850 all in',
                'transit_time' => '28 to 32 days door to door',
                'valid_until' => 'End of the month',
                'body' => 'The rate covers the main freight leg, terminal handling and the customs entry at destination.',
                'mark_quoted' => '1',
            ])
            ->assertRedirect();

        $reply = QuoteReply::firstOrFail();
        $quote->refresh();

        $this->assertSame('USD 2,850 all in', $reply->rateLine());
        $this->assertSame($administrator->id, $reply->user_id);
        $this->assertSame(QuoteStatus::Quoted, $quote->status);
        $this->assertNotNull($quote->replied_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'quote.replied']);

        Notification::assertSentOnDemand(
            QuoteReplySent::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'customer@example.com',
        );
    }

    public function test_the_quotation_appears_on_the_request_afterwards(): void
    {
        $administrator = $this->administrator();
        $quote = QuoteRequest::factory()->create();

        $this->actingAs($administrator)->post(route('admin.quotes.reply', $quote), [
            'subject' => 'Quotation for your enquiry',
            'body' => 'Rate and transit time as discussed on the telephone this morning.',
        ]);

        $this->actingAs($administrator)
            ->get(route('admin.quotes.show', $quote))
            ->assertOk()
            ->assertSee('Quotations sent')
            ->assertSee('Rate and transit time as discussed');
    }

    public function test_a_quotation_needs_a_subject_and_a_message(): void
    {
        $quote = QuoteRequest::factory()->create();

        $this->actingAs($this->administrator())
            ->post(route('admin.quotes.reply', $quote), ['subject' => '', 'body' => 'too short'])
            ->assertSessionHasErrors(['subject', 'body']);

        $this->assertSame(0, QuoteReply::count());
    }

    public function test_a_representative_cannot_see_or_answer_quote_requests(): void
    {
        $quote = QuoteRequest::factory()->create();

        $this->actingAs($this->representative())->get(route('admin.quotes.index'))->assertForbidden();
        $this->actingAs($this->representative())->get(route('admin.quotes.show', $quote))->assertForbidden();
        $this->actingAs($this->representative())
            ->post(route('admin.quotes.reply', $quote), ['subject' => 'Quote', 'body' => 'Sending a rate myself.'])
            ->assertForbidden();

        $this->assertSame(0, QuoteReply::count());
    }

    public function test_requests_can_be_filtered_by_country(): void
    {
        $chinaLane = QuoteRequest::factory()->create(['origin_country' => 'China', 'destination_country' => 'Nigeria', 'name' => 'China Lane']);
        $indiaLane = QuoteRequest::factory()->create(['origin_country' => 'India', 'destination_country' => 'Ghana', 'name' => 'India Lane']);

        $this->actingAs($this->administrator())
            ->get(route('admin.quotes.index', ['country' => 'China']))
            ->assertOk()
            ->assertSee($chinaLane->reference)
            ->assertDontSee($indiaLane->reference);
    }
}

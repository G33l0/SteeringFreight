<?php

namespace Tests\Feature\Public;

use App\Models\ChatConversation;
use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use App\Models\Shipment;
use App\Notifications\ContactMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

/**
 * What a customer sees when the site's own mail server is broken.
 *
 * The queue connection ships as `sync` because shared hosting has no worker,
 * so notifications go out inside the web request. An unreachable SMTP server
 * therefore used to throw mid request and show a 500 page to somebody whose
 * message had already been saved — the worst of both worlds, since the enquiry
 * was sitting in the dashboard while the customer believed the site was broken
 * and went elsewhere.
 *
 * Every test here breaks the mail transport on purpose.
 */
class MailOutageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        $this->setSetting('notifications.admin_email', 'operations@example.test');
    }

    /** Make every attempt to send mail fail, the way a wrong SMTP host does. */
    private function breakTheMailServer(): void
    {
        Mail::shouldReceive('mailer')
            ->andThrow(new RuntimeException('Connection could not be established with host smtp.example.test'));
    }

    public function test_the_contact_form_still_works_when_email_is_down(): void
    {
        $this->breakTheMailServer();

        $this->post(route('contact.store'), [
            'name' => 'Chidi Customer',
            'email' => 'chidi@example.test',
            'subject' => 'Rate for two containers',
            'message' => 'Could you quote Lagos to Rotterdam?',
        ])
            ->assertRedirect(route('contact.create'))
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        // The part that actually matters: the enquiry reached the business.
        $this->assertDatabaseHas('contact_messages', [
            'email' => 'chidi@example.test',
            'subject' => 'Rate for two containers',
        ]);
    }

    public function test_a_quote_request_still_works_when_email_is_down(): void
    {
        $this->breakTheMailServer();

        $this->post(route('quote.store'), [
            'name' => 'Chidi Customer',
            'email' => 'chidi@example.test',
            'origin_country' => 'Nigeria',
            'origin_city' => 'Lagos',
            'destination_country' => 'Netherlands',
            'destination_city' => 'Rotterdam',
            'shipping_method' => 'sea_fcl',
            'cargo_type' => 'Processed cocoa in two containers',
        ])
            ->assertRedirect(route('quote.create'))
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        $this->assertSame(1, QuoteRequest::count());

        // The reference number is the customer's receipt; losing it to a mail
        // fault would leave them with nothing to quote back at us.
        $this->assertNotNull(QuoteRequest::first()->reference);
    }

    public function test_a_customer_can_still_start_a_chat_when_email_is_down(): void
    {
        $this->breakTheMailServer();

        $shipment = Shipment::factory()->create([
            'shipment_status_id' => $this->trackingStatus('in-transit')->id,
        ]);

        $this->post(route('track.chat.store', $shipment->tracking_number), [
            'contact_name' => 'Chidi Customer',
            'contact_email' => 'chidi@example.test',
            'body' => 'Where is my container?',
        ])->assertRedirect();

        $this->assertSame(1, ChatConversation::count());
        $this->assertSame(1, ChatConversation::first()->messages()->count());
    }

    public function test_a_staff_reply_reaches_the_customer_even_if_the_alert_email_fails(): void
    {
        $shipment = Shipment::factory()->create([
            'shipment_status_id' => $this->trackingStatus('in-transit')->id,
        ]);
        $conversation = ChatConversation::factory()->create(['shipment_id' => $shipment->id]);
        $admin = $this->administrator();

        $this->breakTheMailServer();

        $this->actingAs($admin)
            ->post(route('admin.messages.reply', $conversation), ['body' => 'It cleared this morning.'])
            ->assertRedirect(route('admin.messages.show', $conversation));

        // The reply is in the thread the customer is watching, which is the
        // delivery that counts. The courtesy email is the only thing lost.
        $this->assertSame('It cleared this morning.', $conversation->messages()->reorder('id', 'desc')->first()->body);
    }

    public function test_a_shipment_event_is_not_recorded_as_notified_when_the_email_failed(): void
    {
        $this->setSetting('notifications.enabled', true);

        $shipment = Shipment::factory()->create([
            'shipment_status_id' => $this->trackingStatus('in-transit')->id,
        ]);
        $admin = $this->administrator();

        $this->breakTheMailServer();

        $this->actingAs($admin)->post(route('admin.shipments.events.store', $shipment), [
            'shipment_status_id' => $this->trackingStatus('delivered')->id,
            'occurred_at' => now()->toDateTimeString(),
            'is_public' => '1',
            'notify_customer' => '1',
        ])->assertRedirect();

        $event = $shipment->events()->reorder('id', 'desc')->first();

        $this->assertNotNull($event, 'the tracking event itself must still be recorded');
        $this->assertFalse(
            (bool) $event->notified_customer,
            'the event must not claim the customer was told when the email never went',
        );
    }

    /**
     * The exception: a quotation exists to be emailed, so the member of staff
     * who pressed Send is told plainly rather than being left to believe it
     * arrived.
     */
    public function test_a_failed_quotation_is_reported_to_the_member_of_staff(): void
    {
        $quote = QuoteRequest::factory()->create(['email' => 'chidi@example.test']);
        $admin = $this->administrator();

        $this->breakTheMailServer();

        $response = $this->actingAs($admin)->post(route('admin.quotes.reply', $quote), [
            'subject' => 'Your quotation',
            'body' => 'Rate and transit time as discussed.',
        ]);

        $response->assertSessionHasErrors('reply');

        // Saved against the enquiry, so it can simply be sent again.
        $this->assertSame(1, $quote->replies()->count());
    }

    public function test_the_contact_form_is_normal_when_email_works(): void
    {
        Notification::fake();

        $this->post(route('contact.store'), [
            'name' => 'Chidi Customer',
            'email' => 'chidi@example.test',
            'subject' => 'Rate for two containers',
            'message' => 'Could you quote Lagos to Rotterdam?',
        ])->assertSessionHas('status');

        Notification::assertSentTimes(ContactMessageReceived::class, 1);
        $this->assertSame(1, ContactMessage::count());
    }
}

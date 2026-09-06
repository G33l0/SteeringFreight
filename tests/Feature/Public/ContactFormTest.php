<?php

namespace Tests\Feature\Public;

use App\Enums\ContactStatus;
use App\Models\ContactMessage;
use App\Notifications\ContactMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
        Notification::fake();
    }

    public function test_the_contact_page_is_reachable(): void
    {
        $this->get(route('contact.create'))->assertOk()->assertSee('Contact us');
    }

    public function test_a_visitor_can_send_a_message(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Tomas Berg',
            'email' => 'tomas@example.com',
            'subject' => 'Export booking',
            'message' => 'We have a consignment ready next week and would like to discuss options.',
        ])->assertRedirect(route('contact.create'))->assertSessionHas('status');

        $message = ContactMessage::firstOrFail();

        $this->assertSame('Export booking', $message->subject);
        $this->assertSame(ContactStatus::New, $message->status);

        Notification::assertSentOnDemand(ContactMessageReceived::class);
    }

    public function test_the_contact_form_validates_its_input(): void
    {
        $this->post(route('contact.store'), ['name' => '', 'email' => 'nope', 'message' => 'short'])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_opening_a_message_marks_it_as_read(): void
    {
        $message = ContactMessage::factory()->create();

        $this->actingAs($this->administrator())
            ->get(route('admin.contact-messages.show', $message))
            ->assertOk()
            ->assertSee($message->subject);

        $this->assertSame(ContactStatus::Read, $message->fresh()->status);
    }

    public function test_an_administrator_can_update_a_message(): void
    {
        $message = ContactMessage::factory()->create();

        $this->actingAs($this->administrator())->put(route('admin.contact-messages.update', $message), [
            'status' => ContactStatus::Replied->value,
            'internal_notes' => 'Replied with the warehouse rates.',
        ])->assertRedirect();

        $this->assertSame(ContactStatus::Replied, $message->fresh()->status);
    }
}

<?php

namespace App\Notifications;

use App\Models\QuoteReply;
use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The quotation sent back to the customer from the admin panel.
 *
 * The reply-to address is the operations desk, so the customer's answer lands
 * in the shared mailbox rather than a personal one.
 */
class QuoteReplySent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly QuoteRequest $quote,
        public readonly QuoteReply $reply,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $operations = setting('contact.operations_email')
            ?: (setting('contact.email') ?: setting('notifications.admin_email'));

        $message = (new MailMessage)
            ->subject($this->reply->subject)
            ->greeting("Hello {$this->quote->name},")
            ->line("Thank you for your enquiry, reference {$this->quote->reference}.")
            ->line("**Route:** {$this->quote->origin} to {$this->quote->destination}");

        if ($rate = $this->reply->rateLine()) {
            $message->line("**Rate:** {$rate}");
        }

        if ($this->reply->transit_time) {
            $message->line("**Estimated transit time:** {$this->reply->transit_time}");
        }

        if ($this->reply->valid_until) {
            $message->line("**Valid until:** {$this->reply->valid_until}");
        }

        $message->line($this->reply->body);

        if (filled($operations) && filter_var($operations, FILTER_VALIDATE_EMAIL)) {
            $message->replyTo($operations, company_name());
        }

        return $message->salutation(
            setting('notifications.signature', 'Operations desk')."\n".$this->reply->sender_name
        );
    }
}

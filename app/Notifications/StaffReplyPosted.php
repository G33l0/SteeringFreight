<?php

namespace App\Notifications;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Lets the customer know a reply is waiting on the tracking page.
 */
class StaffReplyPosted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ChatConversation $conversation,
        public readonly ChatMessage $message,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tracking = $this->conversation->shipment->tracking_number;

        return (new MailMessage)
            ->subject("Reply about shipment {$tracking}")
            ->greeting("Hello {$this->conversation->contact_name},")
            ->line("Our team has replied to your message about shipment {$tracking}.")
            ->line(Str::limit($this->message->body, 300))
            ->action('Open the tracking page', route('track.show', $tracking))
            ->line('Replies are shown on the tracking page for this shipment.')
            ->salutation(setting('notifications.signature', 'Operations desk')."\n".company_name());
    }
}

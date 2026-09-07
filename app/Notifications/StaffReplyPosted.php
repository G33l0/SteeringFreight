<?php

namespace App\Notifications;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Lets the customer know a reply is waiting on the tracking page.
 *
 * The reply is not repeated in the email: the conversation lives on the
 * tracking page only, and is cleared once it is past the retention window.
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
            ->action('Open the tracking page', route('track.show', $tracking))
            ->line('The reply is shown in the conversation window on that page, which is cleared '.chat_retention_hours().' hours after the last message.')
            ->salutation(setting('notifications.signature', 'Operations desk'));
    }
}

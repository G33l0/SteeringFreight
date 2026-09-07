<?php

namespace App\Notifications;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Internal alert sent to the operations address when a customer writes in.
 *
 * The alert says a message is waiting and links to it. What the customer wrote
 * stays in the chat window, which is cleared on a fixed schedule, so it is not
 * copied into a mailbox that keeps it indefinitely.
 */
class NewCustomerMessage extends Notification implements ShouldQueue
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
            ->subject("New customer message: {$tracking}")
            ->line("{$this->conversation->contact_name} ({$this->conversation->contact_email}) sent a message about shipment {$tracking}.")
            ->line('The message itself is only in the conversation window, which clears '.chat_retention_hours().' hours after the last reply.')
            ->action('Open the conversation', route('admin.messages.show', $this->conversation));
    }
}

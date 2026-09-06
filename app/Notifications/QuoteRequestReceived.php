<?php

namespace App\Notifications;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Internal alert for a new quote request.
 */
class QuoteRequestReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly QuoteRequest $quote) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Quote request {$this->quote->reference}")
            ->line("{$this->quote->name} ({$this->quote->email}) asked for a quote.")
            ->line("Route: {$this->quote->origin} to {$this->quote->destination}")
            ->line('Method: '.($this->quote->shipping_method?->label() ?? 'Not specified'))
            ->line('Cargo: '.($this->quote->cargo_type ?: 'Not specified'))
            ->action('Open the request', route('admin.quotes.show', $this->quote));
    }
}

<?php

namespace App\Notifications;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The quote request as it reaches the operations desk.
 *
 * Everything the customer filled in is in the body, and the reply-to address is
 * the customer, so pressing Reply in webmail answers them directly. The same
 * request is waiting in the admin panel, where a reply can be sent and recorded
 * against the enquiry instead.
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
        $quote = $this->quote;

        $message = (new MailMessage)
            ->subject("Quote request {$quote->reference}: {$quote->origin} to {$quote->destination}")
            ->replyTo($quote->email, $quote->name)
            ->greeting("Quote request {$quote->reference}")
            ->line("**From:** {$quote->name}".($quote->company ? " ({$quote->company})" : ''))
            ->line("**Email:** {$quote->email}");

        if ($quote->phone) {
            $message->line("**Telephone:** {$quote->phone}");
        }

        $message
            ->line("**Route:** {$quote->origin} to {$quote->destination}")
            ->line('**Method:** '.($quote->shipping_method?->label() ?? 'No preference'));

        foreach ([
            'Incoterm' => $quote->incoterm,
            'Goods' => $quote->cargo_type,
            'Gross weight' => $quote->approximate_weight,
            'Packages' => $quote->package_count,
            'Dimensions' => $quote->dimensions,
            'Commercial value' => $quote->goods_value,
            'Cargo ready' => $quote->ready_date?->format('j F Y'),
        ] as $label => $value) {
            if (filled($value)) {
                $message->line("**{$label}:** {$value}");
            }
        }

        if ($quote->message) {
            $message->line('**Notes from the customer:**')->line($quote->message);
        }

        return $message
            ->action('Open and reply in the admin panel', route('admin.quotes.show', $quote))
            ->line('Replying to this email goes straight to the customer.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'quote_id' => $this->quote->getKey(),
            'reference' => $this->quote->reference,
        ];
    }
}

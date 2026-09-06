<?php

namespace App\Notifications;

use App\Models\Shipment;
use App\Models\ShipmentEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the customer when a tracking event is added for a status that has
 * customer notifications switched on.
 */
class ShipmentStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly ShipmentEvent $event,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->event->status?->publicName() ?? 'Update';

        $message = (new MailMessage)
            ->subject("{$this->shipment->tracking_number}: {$status}")
            ->greeting('Shipment update')
            ->line("Tracking number: {$this->shipment->tracking_number}")
            ->line("Status: {$status}");

        if ($this->event->location) {
            $message->line("Location: {$this->event->location}");
        }

        if ($this->event->description) {
            $message->line($this->event->description);
        }

        if ($this->shipment->estimated_delivery) {
            $message->line('Estimated delivery: '.$this->shipment->estimated_delivery->format('j F Y'));
        }

        return $message
            ->action('View tracking page', route('track.show', $this->shipment->tracking_number))
            ->salutation(setting('notifications.signature', 'Operations desk'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'shipment_id' => $this->shipment->getKey(),
            'tracking_number' => $this->shipment->tracking_number,
            'status' => $this->event->status?->name,
        ];
    }
}

<?php

namespace App\Services;

use App\Enums\StatusCategory;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\ShipmentStatus;
use App\Models\User;
use App\Notifications\ShipmentStatusUpdated;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

/**
 * Shipment writes that touch more than one table: creating a shipment, adding
 * tracking events, moving the current status and keeping the cached progress
 * position in step.
 */
class ShipmentService
{
    public function __construct(
        private readonly TrackingNumberGenerator $trackingNumbers,
        private readonly AuditLogger $audit,
        private readonly Settings $settings,
        private readonly Notifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, User $user): Shipment
    {
        return DB::transaction(function () use ($attributes, $user): Shipment {
            $trackingNumber = $attributes['tracking_number'] ?? null;

            $shipment = new Shipment($attributes);
            $shipment->tracking_number = $trackingNumber ?: $this->trackingNumbers->generate();
            $shipment->created_by = $user->getKey();
            $shipment->updated_by = $user->getKey();
            $shipment->status_updated_at = now();
            $shipment->progress_stage = $this->stageFor($shipment->shipment_status_id);
            $shipment->save();

            $status = $shipment->status;

            if ($status) {
                $this->recordEvent($shipment, [
                    'shipment_status_id' => $status->getKey(),
                    'location' => $shipment->current_location,
                    'occurred_at' => now(),
                    'description' => $this->defaultEventDescription($status, $shipment),
                    'is_public' => true,
                ], $user, notify: false);
            }

            $this->audit->record(
                'shipment.created',
                $shipment,
                "Created shipment {$shipment->tracking_number}",
                ['tracking_number' => $shipment->tracking_number],
                $user,
            );

            return $shipment->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Shipment $shipment, array $attributes, User $user): Shipment
    {
        return DB::transaction(function () use ($shipment, $attributes, $user): Shipment {
            $previousStatusId = $shipment->shipment_status_id;

            // A tracking number is issued once and never cleared by an edit.
            if (blank($attributes['tracking_number'] ?? null)) {
                unset($attributes['tracking_number']);
            }

            $shipment->fill($attributes);
            $shipment->updated_by = $user->getKey();

            if ($shipment->shipment_status_id !== $previousStatusId) {
                $shipment->status_updated_at = now();
                $shipment->progress_stage = $this->stageFor($shipment->shipment_status_id, $shipment->progress_stage);
            }

            $changes = AuditLogger::changes($shipment);
            $shipment->save();

            $this->audit->record(
                'shipment.updated',
                $shipment,
                "Updated shipment {$shipment->tracking_number}",
                $changes,
                $user,
            );

            if ($shipment->shipment_status_id !== $previousStatusId && $shipment->status) {
                $this->audit->record(
                    'shipment.status_changed',
                    $shipment,
                    "Status set to {$shipment->status->name} for {$shipment->tracking_number}",
                    ['status' => $shipment->status->name],
                    $user,
                );
            }

            return $shipment;
        });
    }

    /**
     * Add a tracking event and move the shipment on to the event's status.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function addEvent(Shipment $shipment, array $attributes, User $user): ShipmentEvent
    {
        return DB::transaction(function () use ($shipment, $attributes, $user): ShipmentEvent {
            $updateShipment = (bool) ($attributes['update_shipment'] ?? true);
            $notify = (bool) ($attributes['notify_customer'] ?? true);

            $event = $this->recordEvent($shipment, $attributes, $user, notify: $notify, updateShipment: $updateShipment);

            $this->audit->record(
                'shipment.event_created',
                $shipment,
                "Added tracking update to {$shipment->tracking_number}",
                ['event_id' => $event->getKey(), 'status' => $event->status?->name],
                $user,
            );

            return $event;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateEvent(ShipmentEvent $event, array $attributes, User $user): ShipmentEvent
    {
        return DB::transaction(function () use ($event, $attributes, $user): ShipmentEvent {
            $event->fill($attributes);
            $changes = AuditLogger::changes($event);
            $event->save();

            $this->resyncShipment($event->shipment);

            $this->audit->record(
                'shipment.event_updated',
                $event->shipment,
                "Edited tracking update on {$event->shipment->tracking_number}",
                $changes + ['event_id' => $event->getKey()],
                $user,
            );

            return $event;
        });
    }

    public function deleteEvent(ShipmentEvent $event, User $user): void
    {
        DB::transaction(function () use ($event, $user): void {
            $shipment = $event->shipment;
            $eventId = $event->getKey();
            $event->delete();

            $this->resyncShipment($shipment);

            $this->audit->record(
                'shipment.event_deleted',
                $shipment,
                "Deleted tracking update from {$shipment->tracking_number}",
                ['event_id' => $eventId],
                $user,
            );
        });
    }

    public function archive(Shipment $shipment, User $user): void
    {
        $shipment->forceFill(['archived_at' => now(), 'updated_by' => $user->getKey()])->save();

        $this->audit->record('shipment.archived', $shipment, "Archived {$shipment->tracking_number}", [], $user);
    }

    public function restore(Shipment $shipment, User $user): void
    {
        $shipment->forceFill(['archived_at' => null, 'updated_by' => $user->getKey()])->save();

        $this->audit->record('shipment.restored', $shipment, "Restored {$shipment->tracking_number}", [], $user);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function recordEvent(
        Shipment $shipment,
        array $attributes,
        User $user,
        bool $notify = true,
        bool $updateShipment = true,
    ): ShipmentEvent {
        $event = new ShipmentEvent([
            'shipment_status_id' => $attributes['shipment_status_id'] ?? null,
            'location' => $attributes['location'] ?? null,
            'occurred_at' => $attributes['occurred_at'] ?? now(),
            'description' => $attributes['description'] ?? null,
            'internal_note' => $attributes['internal_note'] ?? null,
            'is_public' => (bool) ($attributes['is_public'] ?? true),
        ]);
        $event->shipment_id = $shipment->getKey();
        $event->created_by = $user->getKey();
        $event->save();

        $status = $event->status;

        if ($updateShipment) {
            $shipment->forceFill(array_filter([
                'shipment_status_id' => $status?->getKey(),
                'current_location' => $event->location,
            ], fn ($value) => $value !== null))->forceFill([
                'status_updated_at' => now(),
                'updated_by' => $user->getKey(),
            ]);

            if ($status) {
                $shipment->progress_stage = $this->stageFor($status->getKey(), $shipment->progress_stage);

                if ($status->is_final) {
                    $shipment->delivered_at ??= $event->occurred_at;
                }
            }

            $shipment->save();
        }

        if ($notify && $event->is_public && $status && $this->shouldNotify($shipment, $status)) {
            // The tracking record is the source of truth and is already saved.
            // Only claim the customer was told when the email actually went,
            // so the event list does not lie to the person reading it.
            $sent = $this->notifier->toAddress(
                $shipment->customerContactEmail(),
                new ShipmentStatusUpdated($shipment->fresh(['status']), $event),
            );

            if ($sent) {
                $event->forceFill(['notified_customer' => true])->save();
            }
        }

        return $event;
    }

    private function shouldNotify(Shipment $shipment, ShipmentStatus $status): bool
    {
        return $this->settings->bool('notifications.enabled')
            && $status->notify_customer
            && $shipment->notificationsAllowed();
    }

    /**
     * Recalculate the current status and progress from the event history,
     * used after an event is edited or removed.
     */
    private function resyncShipment(Shipment $shipment): void
    {
        $latest = $shipment->events()->with('status')->first();

        $highestMilestone = $shipment->events()
            ->whereHas('status', fn ($query) => $query->where('category', StatusCategory::Milestone->value))
            ->with('status')
            ->get()
            ->max(fn (ShipmentEvent $event) => $event->status?->stage ?? 0);

        $shipment->forceFill([
            'shipment_status_id' => $latest?->shipment_status_id ?? $shipment->shipment_status_id,
            'progress_stage' => (int) ($highestMilestone ?? 0),
        ])->save();
    }

    private function stageFor(?int $statusId, int $current = 0): int
    {
        if (! $statusId) {
            return $current;
        }

        $status = ShipmentStatus::find($statusId);

        if (! $status || $status->category !== StatusCategory::Milestone) {
            return $current;
        }

        return max($current, (int) $status->stage);
    }

    private function defaultEventDescription(ShipmentStatus $status, Shipment $shipment): string
    {
        $description = $status->description ?: $status->publicName();

        return $shipment->current_location
            ? $description.' Current location: '.$shipment->current_location.'.'
            : $description;
    }
}

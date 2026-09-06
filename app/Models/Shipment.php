<?php

namespace App\Models;

use App\Enums\DocumentVisibility;
use App\Enums\ShippingMethod;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    protected $fillable = [
        'tracking_number',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'origin_country',
        'origin_city',
        'destination_country',
        'destination_city',
        'current_location',
        'shipping_method',
        'service_level',
        'cargo_description',
        'package_count',
        'weight_kg',
        'dimensions',
        'declared_value',
        'declared_value_currency',
        'container_number',
        'vessel_name',
        'voyage_number',
        'air_waybill_number',
        'flight_number',
        'bill_of_lading_number',
        'estimated_departure',
        'estimated_arrival',
        'estimated_delivery',
        'delivered_at',
        'shipment_status_id',
        'progress_stage',
        'exception_note',
        'internal_notes',
        'notifications_enabled',
        'is_sample',
        'archived_at',
        'status_updated_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shipping_method' => ShippingMethod::class,
            'estimated_departure' => 'date',
            'estimated_arrival' => 'date',
            'estimated_delivery' => 'date',
            'delivered_at' => 'datetime',
            'archived_at' => 'datetime',
            'status_updated_at' => 'datetime',
            'notifications_enabled' => 'boolean',
            'is_sample' => 'boolean',
            'weight_kg' => 'decimal:3',
            'declared_value' => 'decimal:2',
            'package_count' => 'integer',
            'progress_stage' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<ShipmentStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ShipmentStatus::class, 'shipment_status_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<ShipmentEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)->latest('occurred_at')->latest('id');
    }

    /** @return HasMany<ShipmentEvent, $this> */
    public function publicEvents(): HasMany
    {
        return $this->events()->where('is_public', true);
    }

    /** @return HasMany<ShipmentDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ShipmentDocument::class)->latest('id');
    }

    /** @return HasMany<ShipmentDocument, $this> */
    public function customerDocuments(): HasMany
    {
        return $this->documents()->where('visibility', DocumentVisibility::Customer->value);
    }

    /** @return HasMany<ChatConversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class)->latest('last_message_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isException(): bool
    {
        return (bool) $this->status?->isException();
    }

    public function isDelivered(): bool
    {
        return (bool) $this->status?->is_final;
    }

    public function originLabel(): string
    {
        return $this->placeLabel($this->origin_city, $this->origin_country);
    }

    public function destinationLabel(): string
    {
        return $this->placeLabel($this->destination_city, $this->destination_country);
    }

    public function routeLabel(): string
    {
        $origin = $this->originLabel();
        $destination = $this->destinationLabel();

        if ($origin === '' || $destination === '') {
            return $origin.$destination;
        }

        return $origin.' to '.$destination;
    }

    private function placeLabel(?string $city, ?string $country): string
    {
        return collect([$city, $country])->filter()->implode(', ');
    }

    public function progressPercent(): int
    {
        $highest = ShipmentStatus::highestStage();

        if ($highest < 1) {
            return 0;
        }

        return (int) round(min($this->progress_stage, $highest) / $highest * 100);
    }

    public function customerContactName(): string
    {
        return $this->customer_name ?: (string) $this->customer?->name;
    }

    public function customerContactEmail(): ?string
    {
        return $this->customer_email ?: $this->customer?->email;
    }

    public function notificationsAllowed(): bool
    {
        return $this->notifications_enabled
            && $this->customerContactEmail() !== null
            && ($this->customer?->notifications_enabled ?? true);
    }

    public function methodLabel(): string
    {
        return $this->shipping_method?->label() ?? 'Not specified';
    }

    /** @param Builder<Shipment> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /** @param Builder<Shipment> $query */
    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    /** @param Builder<Shipment> $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('tracking_number', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%")
                ->orWhere('customer_email', 'like', "%{$term}%")
                ->orWhere('origin_city', 'like', "%{$term}%")
                ->orWhere('origin_country', 'like', "%{$term}%")
                ->orWhere('destination_city', 'like', "%{$term}%")
                ->orWhere('destination_country', 'like', "%{$term}%")
                ->orWhere('container_number', 'like', "%{$term}%")
                ->orWhere('air_waybill_number', 'like', "%{$term}%")
                ->orWhereHas('customer', function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('company', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
        });
    }
}

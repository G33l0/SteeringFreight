<?php

namespace App\Models;

use Database\Factories\ShipmentEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentEvent extends Model
{
    /** @use HasFactory<ShipmentEventFactory> */
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'shipment_status_id',
        'location',
        'occurred_at',
        'description',
        'internal_note',
        'is_public',
        'notified_customer',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'is_public' => 'boolean',
            'notified_customer' => 'boolean',
        ];
    }

    /** @return BelongsTo<Shipment, $this> */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
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

    /** @param Builder<ShipmentEvent> $query */
    public function scopePublic(Builder $query): void
    {
        $query->where('is_public', true);
    }
}

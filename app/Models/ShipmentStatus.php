<?php

namespace App\Models;

use App\Enums\StatusCategory;
use Database\Factories\ShipmentStatusFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ShipmentStatus extends Model
{
    /** @use HasFactory<ShipmentStatusFactory> */
    use HasFactory;

    public const CACHE_KEY = 'shipment_statuses.timeline';

    protected $fillable = [
        'name',
        'slug',
        'category',
        'stage',
        'sort_order',
        'colour',
        'customer_label',
        'description',
        'is_active',
        'is_final',
        'notify_customer',
        'requires_explanation',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => StatusCategory::class,
            'stage' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_final' => 'boolean',
            'notify_customer' => 'boolean',
            'requires_explanation' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCachedTimeline());
        static::deleted(fn () => static::forgetCachedTimeline());
    }

    public static function forgetCachedTimeline(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The ordered list of milestone statuses used to draw the tracking timeline.
     *
     * Only the raw rows are cached; models are rebuilt from them on read, so
     * the cache never holds serialised Eloquent objects.
     *
     * @return Collection<int, ShipmentStatus>
     */
    public static function timeline(): Collection
    {
        $rows = Cache::remember(self::CACHE_KEY, now()->addHours(6), function (): array {
            return static::query()
                ->where('category', StatusCategory::Milestone->value)
                ->where('is_active', true)
                ->orderBy('stage')
                ->orderBy('sort_order')
                ->get()
                ->map->getRawOriginal()
                ->all();
        });

        return static::hydrate($rows);
    }

    public static function highestStage(): int
    {
        return (int) static::timeline()->max('stage');
    }

    /** @return HasMany<Shipment, $this> */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /** @return HasMany<ShipmentEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class);
    }

    public function isException(): bool
    {
        return $this->category === StatusCategory::Exception;
    }

    public function publicName(): string
    {
        return $this->customer_label ?: $this->name;
    }

    /** @param Builder<ShipmentStatus> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<ShipmentStatus> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('category')->orderBy('sort_order')->orderBy('name');
    }
}

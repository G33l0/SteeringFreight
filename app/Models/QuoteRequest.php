<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Enums\ShippingMethod;
use Database\Factories\QuoteRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QuoteRequest extends Model
{
    /** @use HasFactory<QuoteRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'reference',
        'name',
        'email',
        'phone',
        'company',
        'origin',
        'destination',
        'shipping_method',
        'cargo_type',
        'approximate_weight',
        'package_count',
        'ready_date',
        'message',
        'status',
        'internal_notes',
        'handled_by',
        'handled_at',
        'ip_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'shipping_method' => ShippingMethod::class,
            'ready_date' => 'date',
            'handled_at' => 'datetime',
            'package_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (QuoteRequest $quote): void {
            $quote->reference ??= static::generateReference();
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'QR-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    /** @return BelongsTo<User, $this> */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** @param Builder<QuoteRequest> $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('reference', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('company', 'like', "%{$term}%")
                ->orWhere('origin', 'like', "%{$term}%")
                ->orWhere('destination', 'like', "%{$term}%");
        });
    }
}

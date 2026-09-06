<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'company',
        'location',
        'rating',
        'body',
        'photo_path',
        'service_used',
        'is_published',
        'is_sample',
        'sort_order',
        'reviewed_on',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_published' => 'boolean',
            'is_sample' => 'boolean',
            'sort_order' => 'integer',
            'reviewed_on' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param Builder<Review> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param Builder<Review> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->latest('reviewed_on')->latest('id');
    }
}

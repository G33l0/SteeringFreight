<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A quotation sent to the customer from the admin panel.
 */
class QuoteReply extends Model
{
    protected $fillable = [
        'quote_request_id',
        'user_id',
        'sender_name',
        'subject',
        'body',
        'quoted_amount',
        'currency',
        'transit_time',
        'valid_until',
    ];

    /** @return BelongsTo<QuoteRequest, $this> */
    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rateLine(): ?string
    {
        if (blank($this->quoted_amount)) {
            return null;
        }

        return trim(($this->currency ? $this->currency.' ' : '').$this->quoted_amount);
    }
}

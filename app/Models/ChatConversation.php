<?php

namespace App\Models;

use App\Enums\ConversationStatus;
use Database\Factories\ChatConversationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChatConversation extends Model
{
    /** @use HasFactory<ChatConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'customer_id',
        'subject',
        'contact_name',
        'contact_email',
        'public_token',
        'status',
        'last_message_at',
        'unread_for_staff',
        'unread_for_customer',
        'closed_at',
        'closed_by',
        'ip_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ConversationStatus::class,
            'last_message_at' => 'datetime',
            'closed_at' => 'datetime',
            'unread_for_staff' => 'integer',
            'unread_for_customer' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChatConversation $conversation): void {
            $conversation->public_token ??= Str::random(48);
        });
    }

    /** @return BelongsTo<Shipment, $this> */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<ChatMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('id');
    }

    /** @return HasMany<ChatMessage, $this> */
    public function latestMessage(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->latest('id');
    }

    public function isOpen(): bool
    {
        return $this->status === ConversationStatus::Open;
    }

    /** @param Builder<ChatConversation> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', ConversationStatus::Open->value);
    }
}

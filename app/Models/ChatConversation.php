<?php

namespace App\Models;

use App\Enums\ConversationStatus;
use Carbon\CarbonInterface;
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
        'assigned_to',
        'assigned_at',
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
            'assigned_at' => 'datetime',
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

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
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

    /**
     * When this conversation disappears. The chat is a short lived window, not
     * a record: it is deleted a fixed number of hours after the last message so
     * nothing a customer typed is kept on the server.
     */
    public function expiresAt(): CarbonInterface
    {
        return ($this->last_message_at ?? $this->created_at ?? now())->copy()->addHours(chat_retention_hours());
    }

    public function hasExpired(): bool
    {
        return $this->expiresAt()->isPast();
    }

    /**
     * The cut off before which a conversation is treated as gone, whether or
     * not the scheduled purge has run yet.
     */
    public static function retentionCutoff(): CarbonInterface
    {
        return now()->subHours(chat_retention_hours());
    }

    /**
     * Conversations still inside the retention window. Everything else is
     * unreadable on sight, so a missed cron run cannot keep a thread alive.
     *
     * @param  Builder<ChatConversation>  $query
     */
    public function scopeWithinRetention(Builder $query): void
    {
        $cutoff = static::retentionCutoff();

        $query->where(function (Builder $query) use ($cutoff): void {
            $query->where('last_message_at', '>', $cutoff)
                ->orWhere(function (Builder $query) use ($cutoff): void {
                    $query->whereNull('last_message_at')->where('created_at', '>', $cutoff);
                });
        });
    }

    /**
     * Conversations past the retention window, ready to be deleted.
     *
     * @param  Builder<ChatConversation>  $query
     */
    public function scopeExpired(Builder $query): void
    {
        $cutoff = static::retentionCutoff();

        $query->where(function (Builder $query) use ($cutoff): void {
            $query->where('last_message_at', '<=', $cutoff)
                ->orWhere(function (Builder $query) use ($cutoff): void {
                    $query->whereNull('last_message_at')->where('created_at', '<=', $cutoff);
                });
        });
    }

    public function isAssigned(): bool
    {
        return $this->assigned_to !== null;
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->assigned_to !== null && $this->assigned_to === $user->getKey();
    }

    /**
     * Conversations a customer representative may work on: the ones given to
     * them, and the ones nobody has picked up yet.
     *
     * @param  Builder<ChatConversation>  $query
     */
    public function scopeForRepresentative(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user): void {
            $query->where('assigned_to', $user->getKey())->orWhereNull('assigned_to');
        });
    }

    /** @param Builder<ChatConversation> $query */
    public function scopeAssignedTo(Builder $query, User $user): void
    {
        $query->where('assigned_to', $user->getKey());
    }

    /** @param Builder<ChatConversation> $query */
    public function scopeUnassigned(Builder $query): void
    {
        $query->whereNull('assigned_to');
    }

    /** @param Builder<ChatConversation> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', ConversationStatus::Open->value);
    }
}

<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'job_title',
        'phone',
        'is_active',
        'access_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'login_code_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'suspended_at' => 'datetime',
            'access_expires_at' => 'datetime',
            'login_code_expires_at' => 'datetime',
            'login_code_sent_at' => 'datetime',
            'login_code_attempts' => 'integer',
        ];
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /** @return HasMany<ChatConversation, $this> */
    public function assignedConversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class, 'assigned_to');
    }

    /**
     * Every gate and policy in the panel resolves through here, which is why
     * suspension is answered in this one method: a paused or expired account
     * fails every ability at once, so there is no screen left where it could
     * still change a shipment or a tracking number.
     */
    public function hasPermission(string $ability): bool
    {
        if (! $this->is_active || $this->isSuspended()) {
            return false;
        }

        $permissions = $this->role->permissions();

        return in_array('*', $permissions, true) || in_array($ability, $permissions, true);
    }

    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator && $this->is_active && ! $this->isSuspended();
    }

    /**
     * A customer representative only answers messages; they never see the
     * shipment screens.
     */
    public function isRepresentative(): bool
    {
        return $this->role === UserRole::Representative;
    }

    /**
     * Paused by an administrator, or past the date their access was granted
     * until. Either way the account can still sign in — it lands on the notice
     * telling them to speak to the administrator — but it can do nothing else.
     */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null || $this->accessHasExpired();
    }

    public function accessHasExpired(): bool
    {
        return $this->access_expires_at !== null && $this->access_expires_at->isPast();
    }

    /**
     * Why the account is suspended, for the notice screen and the staff list.
     */
    public function suspensionReason(): ?string
    {
        return match (true) {
            $this->suspended_at !== null => 'paused',
            $this->accessHasExpired() => 'expired',
            default => null,
        };
    }

    /**
     * Signed in, enabled and not suspended: the account can actually work.
     */
    public function isUsable(): bool
    {
        return $this->is_active && ! $this->isSuspended();
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    /** @param Builder<User> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Accounts that can be given work: enabled, not paused, not expired. Used
     * wherever staff are offered for selection, so a suspended representative
     * is never handed a conversation they cannot open.
     *
     * @param  Builder<User>  $query
     */
    public function scopeUsable(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereNull('suspended_at')
            ->where(fn (Builder $query) => $query
                ->whereNull('access_expires_at')
                ->orWhere('access_expires_at', '>', now()));
    }
}

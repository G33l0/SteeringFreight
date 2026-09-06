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
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'two_factor_confirmed_at' => 'datetime',
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

    public function hasPermission(string $ability): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $permissions = $this->role->permissions();

        return in_array('*', $permissions, true) || in_array($ability, $permissions, true);
    }

    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator && $this->is_active;
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
     * True when two factor authentication has been completed for the account.
     * The columns exist so the feature can be enabled without a migration.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null;
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
}

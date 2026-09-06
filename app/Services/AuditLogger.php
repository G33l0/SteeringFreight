<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Writes the administrative audit trail.
 *
 * Only field names and identifiers are stored. Passwords, tokens and remember
 * me values are stripped before anything is written.
 */
class AuditLogger
{
    public const REDACTED_KEYS = [
        'password', 'password_confirmation', 'current_password', 'remember_token',
        'two_factor_secret', 'two_factor_recovery_codes', 'public_token', 'token',
        'api_key', 'secret',
    ];

    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
        ?User $user = null,
    ): AuditLog {
        $user ??= Auth::user();

        return AuditLog::create([
            'user_id' => $user?->getKey(),
            'user_name' => $user?->name,
            'action' => $action,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'description' => $description ? mb_substr($description, 0, 500) : null,
            'properties' => $properties === [] ? null : self::redact($properties),
            'ip_address' => Request::ip(),
            'user_agent' => mb_substr((string) Request::userAgent(), 0, 500) ?: null,
        ]);
    }

    /**
     * The list of changed attributes for a saved model, ready for the log.
     *
     * @return array<string, mixed>
     */
    public static function changes(Model $model): array
    {
        $changes = collect($model->getChanges())
            ->except(['updated_at'])
            ->keys()
            ->values()
            ->all();

        return $changes === [] ? [] : ['fields' => $changes];
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function redact(array $properties): array
    {
        foreach ($properties as $key => $value) {
            if (in_array(mb_strtolower((string) $key), self::REDACTED_KEYS, true)) {
                $properties[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $properties[$key] = self::redact($value);
            }
        }

        return $properties;
    }
}

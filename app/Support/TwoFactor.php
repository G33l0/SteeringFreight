<?php

namespace App\Support;

use App\Models\User;

/**
 * Whether a sign in needs a one time code, and where the half finished sign in
 * is kept while it is being asked for.
 *
 * Only master admins are asked. A customer representative can read the
 * conversations assigned to them and nothing else, while a master admin can
 * read every customer record on the system, so that is the account worth the
 * extra step.
 */
class TwoFactor
{
    public const SESSION_KEY = 'admin.login.pending';

    public static function enabled(): bool
    {
        return app(Settings::class)->bool('security.two_factor', true);
    }

    public static function requiredFor(User $user): bool
    {
        return self::enabled() && $user->role->requiresTwoFactor();
    }
}

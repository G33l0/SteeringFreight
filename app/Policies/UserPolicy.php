<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function update(User $user, User $subject): bool
    {
        return $user->hasPermission('users.manage');
    }

    /**
     * An administrator may not remove or deactivate their own account, which
     * keeps at least one working administrator on the system.
     */
    public function delete(User $user, User $subject): bool
    {
        return $user->hasPermission('users.manage') && $user->isNot($subject);
    }
}

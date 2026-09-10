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
     * An administrator may not remove their own account, which keeps at least
     * one working administrator on the system.
     */
    public function delete(User $user, User $subject): bool
    {
        return $user->hasPermission('users.manage') && $user->isNot($subject);
    }

    /**
     * Pausing and resuming an account. Same rule as deleting: somebody has to
     * be left who can still open the panel, and pausing yourself out of it
     * would leave nobody able to undo it.
     */
    public function suspend(User $user, User $subject): bool
    {
        return $user->hasPermission('users.manage') && $user->isNot($subject);
    }
}

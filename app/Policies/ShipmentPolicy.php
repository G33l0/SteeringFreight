<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Shipment;
use App\Models\User;

/**
 * Who may see and change a shipment.
 *
 * A master admin works on everything. A customer representative works on the
 * shipments they raised themselves and the ones an administrator handed to
 * them, and on nothing else — the gates in UserRole open the screens, and every
 * decision about an individual shipment is made here.
 *
 * Creating is governed by an allowance rather than a flat yes: a representative
 * may raise tracking numbers until they have used the count the administrator
 * set, and then has to ask for more. That conversation is the point of it.
 */
class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('shipments.view');
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return $user->hasPermission('shipments.view') && $this->handles($user, $shipment);
    }

    public function create(User $user): bool
    {
        return $user->canRaiseTracking();
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return $user->hasPermission('shipments.manage')
            && ! $shipment->isArchived()
            && $this->handles($user, $shipment);
    }

    /**
     * Archiving takes a shipment off the working list for everybody, so it
     * stays with the master admin whatever else a representative may do.
     */
    public function archive(User $user, Shipment $shipment): bool
    {
        return $user->hasPermission('shipments.archive');
    }

    /**
     * Handing a shipment to somebody else is an administrator's decision. A
     * representative assigning work to themselves would make the allowance
     * meaningless.
     */
    public function assign(User $user, Shipment $shipment): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function manageEvents(User $user, Shipment $shipment): bool
    {
        return $user->hasPermission('shipments.manage')
            && ! $shipment->isArchived()
            && $this->handles($user, $shipment);
    }

    public function manageDocuments(User $user, Shipment $shipment): bool
    {
        return $user->hasPermission('documents.manage');
    }

    /**
     * A master admin is never narrowed to particular shipments; anybody else
     * has to be the one who raised it or the one it was given to.
     */
    private function handles(User $user, Shipment $shipment): bool
    {
        return $user->role === UserRole::Administrator || $shipment->isHandledBy($user);
    }
}

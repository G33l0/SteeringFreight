<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('shipments.view');
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return $user->hasPermission('shipments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('shipments.manage');
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return $user->hasPermission('shipments.manage') && ! $shipment->isArchived();
    }

    public function archive(User $user, Shipment $shipment): bool
    {
        return $user->hasPermission('shipments.archive');
    }

    public function manageEvents(User $user, Shipment $shipment): bool
    {
        return $user->hasPermission('shipments.manage') && ! $shipment->isArchived();
    }

    public function manageDocuments(User $user, Shipment $shipment): bool
    {
        return $user->hasPermission('documents.manage');
    }
}

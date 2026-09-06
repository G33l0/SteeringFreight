<?php

namespace App\Policies;

use App\Models\ShipmentDocument;
use App\Models\User;

class ShipmentDocumentPolicy
{
    public function view(User $user, ShipmentDocument $document): bool
    {
        return $user->hasPermission('documents.view');
    }

    public function delete(User $user, ShipmentDocument $document): bool
    {
        return $user->hasPermission('documents.manage');
    }
}

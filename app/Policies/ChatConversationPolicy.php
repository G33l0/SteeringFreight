<?php

namespace App\Policies;

use App\Models\ChatConversation;
use App\Models\User;

/**
 * Who may read and answer a customer conversation.
 *
 * A master admin sees every conversation. A customer representative sees the
 * ones assigned to them plus anything still unassigned, which is how a customer
 * who has just written in gets picked up. Replying to an unassigned
 * conversation claims it, after which it belongs to that representative.
 */
class ChatConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('chat.view');
    }

    public function view(User $user, ChatConversation $conversation): bool
    {
        if (! $user->hasPermission('chat.view')) {
            return false;
        }

        if ($user->hasPermission('chat.manage')) {
            return true;
        }

        return ! $conversation->isAssigned() || $conversation->isAssignedTo($user);
    }

    public function reply(User $user, ChatConversation $conversation): bool
    {
        return $user->hasPermission('chat.reply')
            && $conversation->isOpen()
            && $this->view($user, $conversation);
    }

    /**
     * Closing and reopening stays with the person handling the conversation,
     * or with a master admin.
     */
    public function close(User $user, ChatConversation $conversation): bool
    {
        return $user->hasPermission('chat.reply') && $this->view($user, $conversation);
    }

    /**
     * Handing a conversation to a different representative is a master admin
     * decision.
     */
    public function assign(User $user, ChatConversation $conversation): bool
    {
        return $user->hasPermission('chat.manage');
    }

    /**
     * Starting a conversation with a customer from the shipment screen needs
     * shipment access, so it is a master admin action.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('chat.manage');
    }
}

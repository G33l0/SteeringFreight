<?php

namespace App\Console\Commands;

use App\Services\ChatService;
use Illuminate\Console\Command;

/**
 * Deletes customer conversations once they are past the retention window.
 *
 * The customer chat is a window, not a record: it exists so a customer can ask
 * a question about a shipment while it is moving, and it is cleared afterwards
 * so nothing they typed is kept on the server. This runs hourly from the
 * scheduler; conversations past the window are unreadable in the meantime.
 */
class PurgeChatConversations extends Command
{
    protected $signature = 'portlane:purge-chat';

    protected $description = 'Delete customer conversations, and their messages, once they are past the chat retention window';

    public function handle(ChatService $chat): int
    {
        $hours = chat_retention_hours();
        $removed = $chat->purgeExpired();

        $this->info($removed === 0
            ? "No conversations were older than {$hours} hours."
            : "Deleted {$removed} conversation(s) with no activity in the last {$hours} hours.");

        return self::SUCCESS;
    }
}

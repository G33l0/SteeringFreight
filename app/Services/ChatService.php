<?php

namespace App\Services;

use App\Enums\ConversationStatus;
use App\Enums\MessageSender;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Shipment;
use App\Models\User;
use App\Notifications\NewCustomerMessage;
use App\Notifications\StaffReplyPosted;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Customer chat.
 *
 * Messages are read back by the browser with short polling, which works on any
 * shared host. Nothing in the controllers depends on the transport, so
 * broadcasting can be added later without touching the data model.
 *
 * The chat is a window, not a record. A conversation lives for
 * `portlane.chat.retention_hours` after its last message and is then deleted
 * with everything in it, so nothing a customer wrote is kept. Files cannot be
 * sent through it at all.
 */
class ChatService
{
    /** Session key holding the conversation tokens this visitor owns. */
    public const SESSION_KEY = 'chat.tokens';

    /** How long the throttled sweep waits between runs, in seconds. */
    private const SWEEP_INTERVAL = 900;

    /** Cache key guarding that sweep. */
    private const SWEEP_KEY = 'chat.last_sweep';

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly Settings $settings,
    ) {}

    /**
     * @param  array{contact_name: string, contact_email: string, subject?: string|null, body: string}  $data
     */
    public function startConversation(Shipment $shipment, array $data, Request $request): ChatConversation
    {
        return DB::transaction(function () use ($shipment, $data, $request): ChatConversation {
            $conversation = $shipment->conversations()->create([
                'customer_id' => $shipment->customer_id,
                'subject' => $data['subject'] ?? null,
                'contact_name' => $data['contact_name'],
                'contact_email' => $data['contact_email'],
                'status' => ConversationStatus::Open,
                'ip_address' => $request->ip(),
            ]);

            $this->addCustomerMessage($conversation, $data['body'], $request);

            return $conversation->refresh();
        });
    }

    public function addCustomerMessage(ChatConversation $conversation, string $body, Request $request): ChatMessage
    {
        $message = DB::transaction(function () use ($conversation, $body, $request): ChatMessage {
            $message = $conversation->messages()->create([
                'sender_type' => MessageSender::Customer,
                'sender_name' => $conversation->contact_name,
                'body' => $body,
                'ip_address' => $request->ip(),
            ]);

            $conversation->forceFill([
                'last_message_at' => now(),
                'unread_for_staff' => $conversation->unread_for_staff + 1,
                'status' => ConversationStatus::Open,
                'closed_at' => null,
                'closed_by' => null,
            ])->save();

            return $message;
        });

        $this->audit->record(
            'chat.customer_message',
            $conversation->shipment,
            "Customer message on {$conversation->shipment->tracking_number}",
            ['conversation_id' => $conversation->getKey()],
        );

        $this->notifyStaff($conversation, $message);

        return $message;
    }

    public function addStaffMessage(ChatConversation $conversation, string $body, User $user): ChatMessage
    {
        $message = DB::transaction(function () use ($conversation, $body, $user): ChatMessage {
            $message = $conversation->messages()->create([
                'sender_type' => MessageSender::Staff,
                'user_id' => $user->getKey(),
                'sender_name' => $user->name,
                'body' => $body,
            ]);

            $conversation->forceFill([
                'last_message_at' => now(),
                'unread_for_customer' => $conversation->unread_for_customer + 1,
                'unread_for_staff' => 0,
            ])->save();

            return $message;
        });

        $this->audit->record(
            'chat.staff_message',
            $conversation->shipment,
            "Replied to the customer on {$conversation->shipment->tracking_number}",
            ['conversation_id' => $conversation->getKey()],
            $user,
        );

        if ($conversation->contact_email) {
            Notification::route('mail', $conversation->contact_email)
                ->notify(new StaffReplyPosted($conversation, $message));
        }

        return $message;
    }

    public function markReadByStaff(ChatConversation $conversation): void
    {
        $conversation->messages()
            ->where('sender_type', MessageSender::Customer->value)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation->forceFill(['unread_for_staff' => 0])->save();
    }

    public function markReadByCustomer(ChatConversation $conversation): void
    {
        $conversation->messages()
            ->where('sender_type', MessageSender::Staff->value)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation->forceFill(['unread_for_customer' => 0])->save();
    }

    /**
     * Hand a conversation to a member of staff, or return it to the unassigned
     * queue by passing null.
     */
    public function assign(ChatConversation $conversation, ?User $assignee, User $actor): void
    {
        $conversation->forceFill([
            'assigned_to' => $assignee?->getKey(),
            'assigned_at' => $assignee ? now() : null,
        ])->save();

        $this->audit->record(
            $assignee ? 'chat.assigned' : 'chat.unassigned',
            $conversation->shipment,
            $assignee
                ? "Assigned the conversation on {$conversation->shipment->tracking_number} to {$assignee->name}"
                : "Returned the conversation on {$conversation->shipment->tracking_number} to the unassigned queue",
            ['conversation_id' => $conversation->getKey(), 'assigned_to' => $assignee?->getKey()],
            $actor,
        );
    }

    public function close(ChatConversation $conversation, User $user): void
    {
        $conversation->forceFill([
            'status' => ConversationStatus::Closed,
            'closed_at' => now(),
            'closed_by' => $user->getKey(),
        ])->save();

        $this->audit->record(
            'chat.closed',
            $conversation->shipment,
            "Closed the conversation on {$conversation->shipment->tracking_number}",
            ['conversation_id' => $conversation->getKey()],
            $user,
        );
    }

    public function reopen(ChatConversation $conversation, User $user): void
    {
        $conversation->forceFill([
            'status' => ConversationStatus::Open,
            'closed_at' => null,
            'closed_by' => null,
        ])->save();

        $this->audit->record(
            'chat.reopened',
            $conversation->shipment,
            "Reopened the conversation on {$conversation->shipment->tracking_number}",
            ['conversation_id' => $conversation->getKey()],
            $user,
        );
    }

    /**
     * Remember in the session that this visitor owns the conversation. Holding
     * the public token alone is not enough to read it.
     */
    public function grantSessionAccess(Request $request, ChatConversation $conversation): void
    {
        $tokens = (array) $request->session()->get(self::SESSION_KEY, []);
        $tokens[$conversation->getKey()] = $conversation->public_token;

        $request->session()->put(self::SESSION_KEY, $tokens);
    }

    public function sessionOwnsConversation(Request $request, ChatConversation $conversation): bool
    {
        $tokens = (array) $request->session()->get(self::SESSION_KEY, []);
        $token = $tokens[$conversation->getKey()] ?? null;

        return is_string($token) && hash_equals($conversation->public_token, $token);
    }

    /**
     * Delete every conversation past the retention window, with its messages.
     *
     * @return int the number of conversations removed
     */
    public function purgeExpired(): int
    {
        $removed = 0;

        ChatConversation::query()
            ->expired()
            ->select('id')
            ->chunkById(200, function ($conversations) use (&$removed): void {
                $ids = $conversations->modelKeys();

                DB::transaction(function () use ($ids, &$removed): void {
                    ChatMessage::whereIn('chat_conversation_id', $ids)->delete();
                    $removed += ChatConversation::whereIn('id', $ids)->delete();
                });
            });

        return $removed;
    }

    /**
     * The same purge, run at most once every SWEEP_INTERVAL seconds.
     *
     * The scheduled command is the proper mechanism, but shared hosting cron is
     * easy to forget, so the chat also tidies up after itself while it is being
     * used. Conversations past the window are already unreadable either way.
     */
    public function sweepExpired(): void
    {
        if (! Cache::add(self::SWEEP_KEY, now()->toIso8601String(), self::SWEEP_INTERVAL)) {
            return;
        }

        $this->purgeExpired();
    }

    private function notifyStaff(ChatConversation $conversation, ChatMessage $message): void
    {
        $addresses = collect([
            // The representative handling the conversation, when there is one.
            $conversation->assignee?->email,
            $this->settings->string('notifications.admin_email'),
        ])->filter(fn (?string $address) => $address !== null
            && filter_var($address, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values();

        foreach ($addresses as $address) {
            Notification::route('mail', $address)->notify(new NewCustomerMessage($conversation, $message));
        }
    }
}

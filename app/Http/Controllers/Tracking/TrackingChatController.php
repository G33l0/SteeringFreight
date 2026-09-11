<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Shipment;
use App\Services\ChatService;
use App\Services\TrackingNumberGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Customer side of the shipment conversation.
 *
 * Access needs two things: the tracking number in the URL and the conversation
 * token held in the visitor's session. Knowing a tracking number on its own is
 * never enough to read an existing conversation.
 *
 * Nothing can be uploaded here, and a conversation stops being readable once it
 * is past the retention window, whether or not the scheduled purge has run.
 */
class TrackingChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chat,
        private readonly TrackingNumberGenerator $trackingNumbers,
    ) {}

    public function store(Request $request, string $tracking_number): RedirectResponse
    {
        $shipment = $this->shipment($tracking_number);

        abort_unless((bool) setting('tracking.chat_enabled', true) && ! $shipment->isArchived(), 404);

        $validated = $request->validate([
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email:filter', 'max:180'],
            'subject' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string', 'min:2', 'max:'.config('portlane.chat.message_max_length')],
            'website' => ['prohibited'],
        ], [
            'website.prohibited' => 'Your message could not be sent. Please try again.',
        ]);

        $conversation = $this->chat->startConversation(
            $shipment,
            [
                'contact_name' => $validated['contact_name'],
                'contact_email' => $validated['contact_email'],
                'subject' => $validated['subject'] ?? null,
                'body' => $validated['body'],
            ],
            $request,
        );

        $this->chat->grantSessionAccess($request, $conversation);

        return redirect()
            ->route('track.show', $shipment->tracking_number)
            ->with('status', 'Your message has been sent to the team handling this shipment.')
            ->withFragment('conversation');
    }

    public function reply(Request $request, string $tracking_number, ChatConversation $conversation): RedirectResponse
    {
        $shipment = $this->shipment($tracking_number);
        $this->authoriseConversation($request, $shipment, $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:'.config('portlane.chat.message_max_length')],
        ]);

        $this->chat->addCustomerMessage($conversation, $validated['body'], $request);

        return redirect()
            ->route('track.show', $shipment->tracking_number)
            ->with('status', 'Message sent.')
            ->withFragment('conversation');
    }

    public function messages(Request $request, string $tracking_number, ChatConversation $conversation): JsonResponse
    {
        $shipment = $this->shipment($tracking_number);
        $this->authoriseConversation($request, $shipment, $conversation);

        $after = (int) $request->query('after', 0);

        $messages = $conversation->messages()
            ->when($after > 0, fn ($query) => $query->where('id', '>', $after))
            ->limit(50)
            ->get();

        $this->chat->markReadByCustomer($conversation);

        return response()->json([
            'status' => $conversation->status->value,
            // Who the customer is talking to, and whether anybody has joined
            // yet. The real member of staff behind the reply is never sent to
            // the browser; the panel is where that is visible.
            'agent' => $conversation->agent_alias,
            'waiting' => ! $conversation->hasAgent(),
            'messages' => $messages->map(fn (ChatMessage $message) => [
                'id' => $message->getKey(),
                'from_staff' => $message->fromStaff(),
                'sender' => $message->fromStaff() ? $conversation->agentName() : $message->sender_name,
                'body' => $message->body,
                'sent_at' => $message->created_at?->toDayDateTimeString(),
            ])->values(),
            'expires_at' => $conversation->expiresAt()->toIso8601String(),
        ]);
    }

    private function shipment(string $trackingNumber): Shipment
    {
        return Shipment::where('tracking_number', $this->trackingNumbers->normalise($trackingNumber))->firstOrFail();
    }

    private function authoriseConversation(Request $request, Shipment $shipment, ChatConversation $conversation): void
    {
        abort_unless($conversation->shipment_id === $shipment->getKey(), 404);

        // Past the retention window the conversation is treated as gone, even
        // if the scheduled purge has not deleted it yet.
        abort_if($conversation->hasExpired(), 404);

        abort_unless($this->chat->sessionOwnsConversation($request, $conversation), 403);
    }
}

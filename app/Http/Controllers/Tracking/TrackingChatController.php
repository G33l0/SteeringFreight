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
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Customer side of the shipment conversation.
 *
 * Access needs two things: the tracking number in the URL and the conversation
 * token held in the visitor's session. Knowing a tracking number on its own is
 * never enough to read an existing conversation.
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
            'attachment' => $this->attachmentRules(),
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
            $request->file('attachment'),
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
            'attachment' => $this->attachmentRules(),
        ]);

        $this->chat->addCustomerMessage($conversation, $validated['body'], $request, $request->file('attachment'));

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
            'messages' => $messages->map(fn (ChatMessage $message) => [
                'id' => $message->getKey(),
                'from_staff' => $message->fromStaff(),
                'sender' => $message->fromStaff() ? company_name() : $message->sender_name,
                'body' => $message->body,
                'sent_at' => $message->created_at?->toDayDateTimeString(),
                'attachment' => $message->hasAttachment() ? [
                    'name' => $message->attachment_name,
                    'url' => route('track.chat.attachment', [
                        'tracking_number' => $shipment->tracking_number,
                        'conversation' => $conversation->getKey(),
                        'message' => $message->getKey(),
                    ]),
                ] : null,
            ])->values(),
        ]);
    }

    public function attachment(
        Request $request,
        string $tracking_number,
        ChatConversation $conversation,
        ChatMessage $message,
    ): StreamedResponse {
        $shipment = $this->shipment($tracking_number);
        $this->authoriseConversation($request, $shipment, $conversation);

        abort_unless($message->chat_conversation_id === $conversation->getKey(), 404);
        abort_unless($message->hasAttachment(), 404);

        $disk = Storage::disk(ChatService::DISK);

        abort_unless($disk->exists($message->attachment_path), 404);

        return $disk->download($message->attachment_path, $message->attachment_name);
    }

    private function shipment(string $trackingNumber): Shipment
    {
        return Shipment::where('tracking_number', $this->trackingNumbers->normalise($trackingNumber))->firstOrFail();
    }

    private function authoriseConversation(Request $request, Shipment $shipment, ChatConversation $conversation): void
    {
        abort_unless($conversation->shipment_id === $shipment->getKey(), 404);
        abort_unless($this->chat->sessionOwnsConversation($request, $conversation), 403);
    }

    /** @return list<mixed> */
    private function attachmentRules(): array
    {
        return [
            'nullable',
            'file',
            'max:'.upload_max_kb(),
            'mimes:'.implode(',', (array) config('portlane.uploads.document_mimes')),
        ];
    }
}

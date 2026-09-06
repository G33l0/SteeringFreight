<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConversationStatus;
use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Shipment;
use App\Services\ChatService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConversationController extends Controller
{
    public function __construct(private readonly ChatService $chat) {}

    public function index(Request $request): View
    {
        $this->authorize('chat.view');

        $status = $request->string('status')->value();

        return view('admin.messages.index', [
            'conversations' => ChatConversation::with(['shipment', 'latestMessage'])
                ->when($status === 'open', fn ($query) => $query->where('status', ConversationStatus::Open->value))
                ->when($status === 'closed', fn ($query) => $query->where('status', ConversationStatus::Closed->value))
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $term = $request->string('q')->trim()->value();
                    $query->where(function ($query) use ($term): void {
                        $query->where('contact_name', 'like', "%{$term}%")
                            ->orWhere('contact_email', 'like', "%{$term}%")
                            ->orWhereHas('shipment', fn ($query) => $query->where('tracking_number', 'like', "%{$term}%"));
                    });
                })
                ->orderByDesc('last_message_at')
                ->paginate((int) config('portlane.per_page.admin'))
                ->withQueryString(),
            'filters' => ['status' => $status, 'q' => $request->string('q')->value()],
        ]);
    }

    public function show(ChatConversation $conversation): View
    {
        $this->authorize('chat.view');

        $conversation->load(['messages.user', 'shipment.status']);
        $this->chat->markReadByStaff($conversation);

        return view('admin.messages.show', ['conversation' => $conversation]);
    }

    public function reply(Request $request, ChatConversation $conversation): RedirectResponse
    {
        $this->authorize('chat.reply');

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:'.config('portlane.chat.message_max_length')],
            'attachment' => [
                'nullable', 'file',
                'max:'.upload_max_kb(),
                'mimes:'.implode(',', (array) config('portlane.uploads.document_mimes')),
            ],
        ]);

        $this->chat->addStaffMessage($conversation, $validated['body'], $request->user(), $request->file('attachment'));

        return redirect()->route('admin.messages.show', $conversation)->with('status', 'Reply sent.');
    }

    public function close(Request $request, ChatConversation $conversation): RedirectResponse
    {
        $this->authorize('chat.reply');

        $this->chat->close($conversation, $request->user());

        return back()->with('status', 'Conversation closed.');
    }

    public function reopen(Request $request, ChatConversation $conversation): RedirectResponse
    {
        $this->authorize('chat.reply');

        $this->chat->reopen($conversation, $request->user());

        return back()->with('status', 'Conversation reopened.');
    }

    /**
     * Start a conversation with the customer from the shipment screen.
     */
    public function storeForShipment(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('chat.reply');

        $validated = $request->validate([
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email:filter', 'max:180'],
            'subject' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string', 'min:1', 'max:'.config('portlane.chat.message_max_length')],
        ]);

        $conversation = $shipment->conversations()->create([
            'customer_id' => $shipment->customer_id,
            'subject' => $validated['subject'] ?? null,
            'contact_name' => $validated['contact_name'],
            'contact_email' => $validated['contact_email'],
            'status' => ConversationStatus::Open,
        ]);

        $this->chat->addStaffMessage($conversation, $validated['body'], $request->user());

        return redirect()->route('admin.messages.show', $conversation)->with('status', 'Message sent to the customer.');
    }

    public function attachment(ChatConversation $conversation, ChatMessage $message): StreamedResponse
    {
        $this->authorize('chat.view');

        abort_unless($message->chat_conversation_id === $conversation->getKey(), 404);
        abort_unless($message->hasAttachment(), 404);

        $disk = Storage::disk(ChatService::DISK);
        abort_unless($disk->exists($message->attachment_path), 404);

        return $disk->download($message->attachment_path, $message->attachment_name);
    }
}

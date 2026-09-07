<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConversationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ConversationController extends Controller
{
    public function __construct(private readonly ChatService $chat) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ChatConversation::class);

        // Tidy up while somebody is looking at the queue, in case the scheduler
        // is not running on this host.
        $this->chat->sweepExpired();

        $user = $request->user();
        $status = $request->string('status')->value();
        $queue = $request->string('queue')->value();

        $conversations = ChatConversation::query()
            ->withinRetention()
            ->with(['shipment.status', 'latestMessage', 'assignee'])
            ->unless($user->hasPermission('chat.manage'), fn (Builder $query) => $query->forRepresentative($user))
            ->when($queue === 'mine', fn (Builder $query) => $query->assignedTo($user))
            ->when($queue === 'unassigned', fn (Builder $query) => $query->unassigned())
            ->when($status === 'open', fn (Builder $query) => $query->where('status', ConversationStatus::Open->value))
            ->when($status === 'closed', fn (Builder $query) => $query->where('status', ConversationStatus::Closed->value))
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = $request->string('q')->trim()->value();
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('contact_name', 'like', "%{$term}%")
                        ->orWhere('contact_email', 'like', "%{$term}%")
                        ->orWhereHas('shipment', fn (Builder $query) => $query->where('tracking_number', 'like', "%{$term}%"));
                });
            })
            ->orderByDesc('last_message_at')
            ->paginate((int) config('portlane.per_page.admin'))
            ->withQueryString();

        return view('admin.messages.index', [
            'conversations' => $conversations,
            'filters' => [
                'status' => $status,
                'queue' => $queue,
                'q' => $request->string('q')->value(),
            ],
            'representatives' => $this->representatives($user),
            'counts' => $this->queueCounts($user),
        ]);
    }

    public function show(Request $request, ChatConversation $conversation): View
    {
        abort_if($conversation->hasExpired(), 404);
        $this->authorize('view', $conversation);

        $conversation->load(['messages.user', 'shipment.status', 'shipment.customer', 'assignee']);
        $this->chat->markReadByStaff($conversation);

        return view('admin.messages.show', [
            'conversation' => $conversation,
            'representatives' => $this->representatives($request->user()),
            // Read only tracking context, so whoever answers has the shipment
            // in front of them without being able to change it.
            'events' => $conversation->shipment->events()->with('status')->limit(6)->get(),
        ]);
    }

    public function reply(Request $request, ChatConversation $conversation): RedirectResponse
    {
        abort_if($conversation->hasExpired(), 404);
        $this->authorize('reply', $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:'.config('portlane.chat.message_max_length')],
        ]);

        // Answering an unassigned conversation claims it.
        if (! $conversation->isAssigned()) {
            $this->chat->assign($conversation, $request->user(), $request->user());
        }

        $this->chat->addStaffMessage($conversation, $validated['body'], $request->user());

        return redirect()->route('admin.messages.show', $conversation)->with('status', 'Reply sent.');
    }

    public function assign(Request $request, ChatConversation $conversation): RedirectResponse
    {
        $this->authorize('assign', $conversation);

        $validated = $request->validate([
            'assigned_to' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        $assignee = $validated['assigned_to'] ? User::find($validated['assigned_to']) : null;

        $this->chat->assign($conversation, $assignee, $request->user());

        return back()->with('status', $assignee
            ? "Conversation assigned to {$assignee->name}."
            : 'Conversation returned to the unassigned queue.');
    }

    /**
     * A representative taking an unassigned conversation for themselves.
     */
    public function claim(Request $request, ChatConversation $conversation): RedirectResponse
    {
        $this->authorize('reply', $conversation);

        if ($conversation->isAssigned() && ! $conversation->isAssignedTo($request->user())) {
            return back()->withErrors(['conversation' => 'Somebody else is already handling this conversation.']);
        }

        $this->chat->assign($conversation, $request->user(), $request->user());

        return back()->with('status', 'You are now handling this conversation.');
    }

    public function close(Request $request, ChatConversation $conversation): RedirectResponse
    {
        $this->authorize('close', $conversation);

        $this->chat->close($conversation, $request->user());

        return back()->with('status', 'Conversation closed.');
    }

    public function reopen(Request $request, ChatConversation $conversation): RedirectResponse
    {
        $this->authorize('close', $conversation);

        $this->chat->reopen($conversation, $request->user());

        return back()->with('status', 'Conversation reopened.');
    }

    /**
     * Start a conversation with the customer from the shipment screen.
     */
    public function storeForShipment(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('create', ChatConversation::class);

        $validated = $request->validate([
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email:filter', 'max:180'],
            'subject' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string', 'min:1', 'max:'.config('portlane.chat.message_max_length')],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $conversation = $shipment->conversations()->create([
            'customer_id' => $shipment->customer_id,
            'subject' => $validated['subject'] ?? null,
            'contact_name' => $validated['contact_name'],
            'contact_email' => $validated['contact_email'],
            'status' => ConversationStatus::Open,
            'assigned_to' => $validated['assigned_to'] ?? $request->user()->getKey(),
            'assigned_at' => now(),
        ]);

        $this->chat->addStaffMessage($conversation, $validated['body'], $request->user());

        return redirect()->route('admin.messages.show', $conversation)->with('status', 'Message sent to the customer.');
    }

    /**
     * Staff a conversation can be handed to. Only a master admin sees the list.
     *
     * @return Collection<int, User>
     */
    private function representatives(User $user): Collection
    {
        if (! $user->hasPermission('chat.assign')) {
            return collect();
        }

        return User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::Representative->value, UserRole::Administrator->value])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }

    /**
     * @return array{mine: int, unassigned: int, open: int}
     */
    private function queueCounts(User $user): array
    {
        $scope = fn () => ChatConversation::query()
            ->withinRetention()
            ->unless($user->hasPermission('chat.manage'), fn (Builder $query) => $query->forRepresentative($user));

        return [
            'mine' => (clone $scope())->assignedTo($user)->open()->count(),
            'unassigned' => (clone $scope())->unassigned()->open()->count(),
            'open' => (clone $scope())->open()->count(),
        ];
    }
}

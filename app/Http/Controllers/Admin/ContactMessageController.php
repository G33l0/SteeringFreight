<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactMessageController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('contact.view');

        $status = $request->string('status')->value();

        return view('admin.contact.index', [
            'messages' => ContactMessage::query()
                ->search($request->string('q')->trim()->value())
                ->when(
                    in_array($status, array_keys(ContactStatus::options()), true),
                    fn ($query) => $query->where('status', $status),
                )
                ->latest('id')
                ->paginate((int) config('portlane.per_page.admin'))
                ->withQueryString(),
            'statuses' => ContactStatus::options(),
            'filters' => ['q' => $request->string('q')->value(), 'status' => $status],
        ]);
    }

    public function show(ContactMessage $message): View
    {
        $this->authorize('contact.view');

        if ($message->status === ContactStatus::New) {
            $message->update(['status' => ContactStatus::Read, 'read_at' => now()]);
        }

        return view('admin.contact.show', [
            'message' => $message->load('handler'),
            'statuses' => ContactStatus::options(),
        ]);
    }

    public function update(Request $request, ContactMessage $message): RedirectResponse
    {
        $this->authorize('contact.manage');

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ContactStatus::class)],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $message->update($validated + ['handled_by' => $request->user()->getKey()]);

        $this->audit->record('contact.updated', $message, "Contact message from {$message->name} marked {$message->status->label()}");

        return back()->with('status', 'Message updated.');
    }
}

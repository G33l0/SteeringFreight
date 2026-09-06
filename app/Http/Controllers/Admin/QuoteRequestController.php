<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuoteRequestController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('quotes.view');

        $status = $request->string('status')->value();

        return view('admin.quotes.index', [
            'quotes' => QuoteRequest::query()
                ->search($request->string('q')->trim()->value())
                ->when(
                    in_array($status, array_keys(QuoteStatus::options()), true),
                    fn ($query) => $query->where('status', $status),
                )
                ->when($request->date('from'), fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
                ->when($request->date('to'), fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
                ->latest('id')
                ->paginate((int) config('portlane.per_page.admin'))
                ->withQueryString(),
            'statuses' => QuoteStatus::options(),
            'filters' => [
                'q' => $request->string('q')->value(),
                'status' => $status,
                'from' => $request->date('from')?->toDateString(),
                'to' => $request->date('to')?->toDateString(),
            ],
        ]);
    }

    public function show(QuoteRequest $quote): View
    {
        $this->authorize('quotes.view');

        return view('admin.quotes.show', [
            'quote' => $quote->load('handler'),
            'statuses' => QuoteStatus::options(),
        ]);
    }

    public function update(Request $request, QuoteRequest $quote): RedirectResponse
    {
        $this->authorize('quotes.manage');

        $validated = $request->validate([
            'status' => ['required', Rule::enum(QuoteStatus::class)],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $quote->update($validated + [
            'handled_by' => $request->user()->getKey(),
            'handled_at' => now(),
        ]);

        $this->audit->record('quote.updated', $quote, "Quote {$quote->reference} marked {$quote->status->label()}");

        return back()->with('status', 'Quote request updated.');
    }
}

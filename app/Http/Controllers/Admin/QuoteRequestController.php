<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use App\Notifications\QuoteReplySent;
use App\Services\AuditLogger;
use App\Services\Notifier;
use App\Support\Countries;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuoteRequestController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly Notifier $notifier,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('quotes.view');

        $status = $request->string('status')->value();
        $country = $request->string('country')->value();

        return view('admin.quotes.index', [
            'quotes' => QuoteRequest::query()
                ->withCount('replies')
                ->search($request->string('q')->trim()->value())
                ->when(
                    in_array($status, array_keys(QuoteStatus::options()), true),
                    fn ($query) => $query->where('status', $status),
                )
                ->when($country, fn ($query) => $query->where(function ($query) use ($country): void {
                    $query->where('origin_country', $country)->orWhere('destination_country', $country);
                }))
                ->when($request->date('from'), fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
                ->when($request->date('to'), fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
                ->latest('id')
                ->paginate((int) config('portlane.per_page.admin'))
                ->withQueryString(),
            'statuses' => QuoteStatus::options(),
            'countries' => Countries::names(),
            'counts' => [
                'new' => QuoteRequest::where('status', QuoteStatus::New->value)->count(),
                'awaiting_reply' => QuoteRequest::whereNull('replied_at')->count(),
            ],
            'filters' => [
                'q' => $request->string('q')->value(),
                'status' => $status,
                'country' => $country,
                'from' => $request->date('from')?->toDateString(),
                'to' => $request->date('to')?->toDateString(),
            ],
        ]);
    }

    public function show(QuoteRequest $quote): View
    {
        $this->authorize('quotes.view');

        return view('admin.quotes.show', [
            'quote' => $quote->load(['handler', 'replies.user']),
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

    /**
     * Send the quotation to the customer from the dashboard. The same enquiry
     * can also be answered straight from webmail, because the alert email is
     * addressed to reply to the customer.
     */
    public function reply(Request $request, QuoteRequest $quote): RedirectResponse
    {
        $this->authorize('quotes.manage');

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'min:10', 'max:8000'],
            'quoted_amount' => ['nullable', 'string', 'max:60'],
            'currency' => ['nullable', 'string', 'size:3', 'alpha'],
            'transit_time' => ['nullable', 'string', 'max:80'],
            'valid_until' => ['nullable', 'string', 'max:60'],
            'mark_quoted' => ['boolean'],
        ]);

        $reply = DB::transaction(function () use ($quote, $request, $validated) {
            $reply = $quote->replies()->create([
                'user_id' => $request->user()->getKey(),
                'sender_name' => $request->user()->name,
                'subject' => $validated['subject'],
                'body' => $validated['body'],
                'quoted_amount' => $validated['quoted_amount'] ?? null,
                'currency' => isset($validated['currency']) ? strtoupper($validated['currency']) : null,
                'transit_time' => $validated['transit_time'] ?? null,
                'valid_until' => $validated['valid_until'] ?? null,
            ]);

            $quote->forceFill([
                'replied_at' => now(),
                'handled_by' => $request->user()->getKey(),
                'handled_at' => now(),
                'status' => $request->boolean('mark_quoted', true) ? QuoteStatus::Quoted : $quote->status,
            ])->save();

            return $reply;
        });

        // Here the email is the whole point, so a failure is reported to the
        // person who pressed Send rather than swallowed. The quotation is still
        // recorded against the enquiry, so nothing is lost and it can be
        // resent once mail is working.
        $sent = $this->notifier->toAddress($quote->email, new QuoteReplySent($quote, $reply));

        $this->audit->record(
            'quote.replied',
            $quote,
            "Sent a quotation to {$quote->name} for {$quote->reference}",
            ['reply_id' => $reply->getKey(), 'rate' => $reply->rateLine()],
        );

        if (! $sent) {
            return back()->withErrors([
                'reply' => "The quotation was saved against {$quote->reference}, but it could not be emailed to {$quote->email}. Check the mail settings and send it again.",
            ]);
        }

        return back()->with('status', "Quotation sent to {$quote->email}.");
    }
}

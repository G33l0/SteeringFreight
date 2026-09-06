<?php

namespace App\Http\Controllers;

use App\Enums\ShippingMethod;
use App\Http\Requests\QuoteRequestFormRequest;
use App\Models\QuoteRequest;
use App\Notifications\QuoteRequestReceived;
use App\Support\Countries;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

class QuoteRequestController extends Controller
{
    public function create(): View
    {
        return view('public.quote', [
            'methods' => ShippingMethod::options(),
            'countries' => Countries::names(),
            'frequentCountries' => Countries::frequentlyUsed(),
            'incoterms' => ['EXW', 'FCA', 'FOB', 'CFR', 'CIF', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP'],
            'metaTitle' => 'Request a quote — '.company_name(),
            'metaDescription' => 'Tell us the origin, destination and cargo details and we will come back with a rate and transit time.',
        ]);
    }

    public function store(QuoteRequestFormRequest $request, Settings $settings): RedirectResponse
    {
        $quote = QuoteRequest::create($request->safe()->except('website') + [
            'ip_address' => $request->ip(),
        ]);

        $address = $settings->string('notifications.admin_email');

        if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
            Notification::route('mail', $address)->notify(new QuoteRequestReceived($quote));
        }

        return redirect()
            ->route('quote.create')
            ->with('status', trim($settings->string('quotes.confirmation')).' Your reference is '.$quote->reference.'.');
    }
}

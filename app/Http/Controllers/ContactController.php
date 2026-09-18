<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactFormRequest;
use App\Models\ContactMessage;
use App\Notifications\ContactMessageReceived;
use App\Services\Notifier;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function __construct(private readonly Notifier $notifier) {}

    public function create(): View
    {
        return view('public.contact', [
            'metaTitle' => 'Contact '.company_name(),
            'metaDescription' => 'Speak to our operations desk about a booking, a quotation or an existing shipment.',
        ]);
    }

    public function store(ContactFormRequest $request, Settings $settings): RedirectResponse
    {
        $message = ContactMessage::create($request->safe()->except('website') + [
            'ip_address' => $request->ip(),
        ]);

        // The message is already saved. If the alert to the operations desk
        // cannot be sent, that is the operator's problem to see in the log and
        // in the dashboard, not a broken page for somebody who just wrote in.
        $this->notifier->toAddress(
            $settings->string('notifications.admin_email'),
            new ContactMessageReceived($message),
        );

        return redirect()
            ->route('contact.create')
            ->with('status', 'Thank you for getting in touch. We will reply during business hours.');
    }
}

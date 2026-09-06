<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactFormRequest;
use App\Models\ContactMessage;
use App\Notifications\ContactMessageReceived;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

class ContactController extends Controller
{
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

        $address = $settings->string('notifications.admin_email');

        if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
            Notification::route('mail', $address)->notify(new ContactMessageReceived($message));
        }

        return redirect()
            ->route('contact.create')
            ->with('status', 'Thank you for getting in touch. We will reply during business hours.');
    }
}

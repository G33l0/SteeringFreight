<?php

namespace App\Services;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as Mail;
use Throwable;

/**
 * Sends a notification without letting a mail problem become the visitor's
 * problem.
 *
 * The queue connection ships as `sync`, because shared hosting has no worker,
 * so a notification is delivered inside the web request that triggered it. That
 * makes an unreachable SMTP server an exception thrown mid request — and the
 * customer, whose message has already been written to the database, is shown a
 * 500 page and told nothing worked. It did work; only the alert to staff
 * failed.
 *
 * So delivery failures are logged and reported back as `false`, never thrown.
 * The caller decides what that means: a customer facing form carries on and
 * thanks them, while a screen whose whole purpose was to send an email says so
 * plainly to the member of staff who pressed the button.
 *
 * The one deliberate exception is the sign-in code in LoginCodeService, which
 * must fail closed: a code that could not be sent has to stop the sign in, or
 * the second factor would only apply when the mail server felt like it.
 */
class Notifier
{
    /**
     * @return bool whether the message was handed to the mail transport
     */
    public function toAddress(?string $email, Notification $notification): bool
    {
        if ($email === null || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        try {
            Mail::route('mail', $email)->notify($notification);

            return true;
        } catch (Throwable $exception) {
            // The address is left out deliberately: this is a customer's email
            // address, and an error log is not the place for it.
            Log::error('Could not send a notification email.', [
                'notification' => $notification::class,
                'exception' => $exception,
            ]);

            return false;
        }
    }
}

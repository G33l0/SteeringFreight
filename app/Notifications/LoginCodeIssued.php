<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The one time code for a staff sign in.
 *
 * Deliberately not queued: the person is waiting at the sign in screen, and a
 * code that arrives after a worker gets round to it is a code that has already
 * expired.
 *
 * Email is the only channel. The address is already the account's own and is
 * already the way a password is reset, so it needs no third party, no per
 * message cost and no telephone number on file.
 */
class LoginCodeIssued extends Notification
{
    public function __construct(
        public readonly string $code,
        public readonly int $minutes,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your sign-in code: '.$this->code)
            ->greeting('Sign-in code')
            ->line('Use this code to finish signing in to the '.company_name().' admin panel.')
            ->line('**'.$this->code.'**')
            ->line("The code expires in {$this->minutes} minutes and can only be used once.")
            ->line('If you did not just try to sign in, your password may be known to somebody else. Change it as soon as you are back in, and tell whoever administers the panel.');
    }
}

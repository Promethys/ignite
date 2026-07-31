<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class VerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Get the verify email notification mail message for the given URL.
     *
     * @param  string  $url
     * @return MailMessage
     */
    protected function buildMailMessage($url)
    {
        $expireMinutes = (int) config('auth.verification.expire', 60);
        $appName = config('app.name');

        return (new MailMessage)
            ->subject(Lang::get('mail.verify.subject'))
            ->greeting(Lang::get('mail.verify.greeting'))
            ->line(Lang::get('mail.verify.intro'))
            ->action(Lang::get('mail.verify.button'), $url)
            ->line(Lang::get('mail.verify.expiry', ['count' => $expireMinutes]))
            ->line(Lang::get('mail.verify.outro'))
            ->salutation(Lang::get('mail.verify.salutation', ['app' => $appName]));
    }
}

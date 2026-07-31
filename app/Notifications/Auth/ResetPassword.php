<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class ResetPassword extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    /**
     * Get the reset password notification mail message for the given URL.
     *
     * @param  string  $url
     * @return MailMessage
     */
    protected function buildMailMessage($url)
    {
        $passwordBroker = config('auth.defaults.passwords');
        $expireMinutes = (int) config("auth.passwords.{$passwordBroker}.expire");
        $appName = config('app.name');

        return (new MailMessage)
            ->subject(Lang::get('mail.reset.subject'))
            ->greeting(Lang::get('mail.reset.greeting'))
            ->line(Lang::get('mail.reset.intro'))
            ->action(Lang::get('mail.reset.button'), $url)
            ->line(Lang::get('mail.reset.expiry', ['count' => $expireMinutes]))
            ->line(Lang::get('mail.reset.outro'))
            ->salutation(Lang::get('mail.reset.salutation', ['app' => $appName]));
    }
}

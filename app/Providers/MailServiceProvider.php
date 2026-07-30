<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $replyTo = config('mail.reply_to');

        if (config('mail.reply_to')) {
            Mail::alwaysReplyTo($replyTo);
        }
    }
}

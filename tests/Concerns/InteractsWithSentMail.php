<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;

trait InteractsWithSentMail
{
    protected function lastMessage(): Email
    {
        $message = Mail::getSymfonyTransport()->messages()->last();

        $this->assertNotNull($message, 'No message was sent.');

        return $message->getOriginalMessage();
    }
}

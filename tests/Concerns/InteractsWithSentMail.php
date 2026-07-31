<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;

trait InteractsWithSentMail
{
    protected function lastMessage(): Email
    {
        $message = Mail::getSymfonyTransport()->messages()->last();

        $this->assertNotNull($message, 'No message was sent.');

        return $message->getOriginalMessage();
    }

    protected function makeMailTransportFail(): void
    {
        Mail::extend('always_failing', fn () => new class extends AbstractTransport
        {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('The mail transport is unavailable.');
            }

            public function __toString(): string
            {
                return 'always_failing://';
            }
        });

        config([
            'mail.default' => 'always_failing',
            'mail.mailers.always_failing' => ['transport' => 'always_failing'],
        ]);

        Mail::forgetMailers();
    }
}

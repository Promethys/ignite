<?php

namespace Tests\Feature\Providers;

use App\Models\User;
use App\Providers\MailServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class MailServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_outgoing_mail_carries_the_configured_reply_to_address(): void
    {
        $this->bootMailProviderWithReplyTo('contact@example.test');

        Mail::raw('Body', fn ($message) => $message->to('member@example.test')->subject('Subject'));

        $this->assertSame(['contact@example.test'], $this->replyToAddressesOfLastMessage());
    }

    public function test_outgoing_mail_has_no_reply_to_when_none_is_configured(): void
    {
        $this->bootMailProviderWithReplyTo(null);

        Mail::raw('Body', fn ($message) => $message->to('member@example.test')->subject('Subject'));

        $this->assertSame([], $this->replyToAddressesOfLastMessage());
    }

    public function test_notification_mail_carries_the_configured_reply_to_address(): void
    {
        $this->bootMailProviderWithReplyTo('contact@example.test');

        $user = User::factory()->unverified()->create();

        $user->sendEmailVerificationNotification();

        $this->assertSame(['contact@example.test'], $this->replyToAddressesOfLastMessage());
    }

    private function bootMailProviderWithReplyTo(?string $address): void
    {
        config(['mail.reply_to' => $address]);

        Mail::forgetMailers();

        (new MailServiceProvider($this->app))->boot();
    }

    /**
     * @return array<int, string>
     */
    private function replyToAddressesOfLastMessage(): array
    {
        $sentMessage = Mail::getSymfonyTransport()->messages()->last();

        $this->assertNotNull($sentMessage, 'No message was sent.');

        $email = $sentMessage->getOriginalMessage();

        $this->assertInstanceOf(Email::class, $email);

        return array_map(fn ($address) => $address->getAddress(), $email->getReplyTo());
    }
}

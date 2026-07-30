<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSentMail;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use InteractsWithSentMail;
    use RefreshDatabase;

    public function test_notification_is_queued(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new VerifyEmail);
    }

    public function test_english_user_receives_the_english_verification_subject(): void
    {
        $user = User::factory()->unverified()->create(['locale' => 'en']);

        $user->notify(new VerifyEmail);

        $this->assertSame('Verify your email address', $this->lastMessage()->getSubject());
    }

    public function test_french_user_receives_the_french_verification_subject(): void
    {
        $user = User::factory()->unverified()->create(['locale' => 'fr']);

        $user->notify(new VerifyEmail);

        $this->assertSame('Vérifiez votre adresse e-mail', $this->lastMessage()->getSubject());
    }

    public function test_action_url_is_a_signed_verification_link(): void
    {
        $user = User::factory()->unverified()->create();

        $user->notify(new VerifyEmail);

        $body = $this->lastMessage()->getHtmlBody();

        $this->assertIsString($body);
        $this->assertStringContainsString('/verify-email/', $body);
        $this->assertStringContainsString('expires=', $body);
        $this->assertStringContainsString('signature=', $body);
    }
}

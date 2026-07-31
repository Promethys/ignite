<?php

namespace Tests\Feature\Notifications\Auth;

use App\Models\User;
use App\Notifications\Auth\ResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSentMail;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use InteractsWithSentMail;
    use RefreshDatabase;

    public function test_notification_is_queued(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new ResetPassword('test-token'));
    }

    public function test_english_user_receives_the_english_reset_subject(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $user->notify(new ResetPassword('test-token'));

        $this->assertSame('Reset your password', $this->lastMessage()->getSubject());
    }

    public function test_french_user_receives_the_french_reset_subject(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $user->notify(new ResetPassword('test-token'));

        $this->assertSame('Réinitialisez votre mot de passe', $this->lastMessage()->getSubject());
    }

    public function test_action_url_is_a_valid_reset_link(): void
    {
        $user = User::factory()->create();

        $user->notify(new ResetPassword('test-token'));

        $body = $this->lastMessage()->getHtmlBody();

        $this->assertIsString($body);
        $this->assertStringContainsString('/reset-password/test-token', $body);
        $this->assertStringContainsString('email=', $body);
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithSentMail;
use Tests\TestCase;

class VerificationNotificationTest extends TestCase
{
    use InteractsWithSentMail;
    use RefreshDatabase;

    public function test_sends_verification_notification(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect(route('home'));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_does_not_send_verification_notification_if_email_is_verified(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect(route('dashboard', absolute: false));

        Notification::assertNothingSent();
    }

    public function test_a_transport_failure_reports_an_error_instead_of_success(): void
    {
        $this->makeMailTransportFail();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'verification-link-error');
    }

    public function test_the_verification_error_toast_is_translated_in_every_locale(): void
    {
        foreach (['en', 'fr'] as $locale) {
            $this->app->setLocale($locale);

            $this->assertNotSame(
                'toasts.auth.verification_error',
                __('toasts.auth.verification_error'),
                "The verification_error toast has no {$locale} translation, so users would see the raw key."
            );
        }
    }
}

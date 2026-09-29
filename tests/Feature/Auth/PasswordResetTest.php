<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithSentMail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use InteractsWithSentMail;
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered()
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_a_transport_failure_still_returns_the_neutral_response()
    {
        $this->makeMailTransportFail();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', __('toasts.auth.reset_link'));
    }

    public function test_reset_password_screen_can_be_rendered()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get(route('password.reset', $notification->token));

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'New-P@ssw0rd123',
                'password_confirmation' => 'New-P@ssw0rd123',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'New-P@ssw0rd123',
            'password_confirmation' => 'New-P@ssw0rd123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_a_reset_link_request_rejects_an_email_over_255_characters(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => str_repeat('a', 244).'@example.com'])
            ->assertInvalid(['email' => '255']);

        Notification::assertNothingSent();
    }

    public function test_a_password_reset_rejects_an_email_over_255_characters(): void
    {
        $this->post(route('password.store'), [
            'token' => 'any-token',
            'email' => str_repeat('a', 244).'@example.com',
            'password' => 'New-P@ssw0rd123',
            'password_confirmation' => 'New-P@ssw0rd123',
        ])->assertInvalid(['email' => '255']);
    }
}

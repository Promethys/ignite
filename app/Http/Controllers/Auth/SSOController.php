<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\SocialLoginService;
use App\Services\Auth\UnverifiedSocialEmailException;
use App\Support\GuestLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Laravel\Socialite\Socialite;

class SSOController extends Controller
{
    public function __construct(private readonly SocialLoginService $socialLogin) {}

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        try {
            $ssoUser = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            Log::error('SSO provider error', [
                'category' => 'auth',
                'provider' => $provider,
                'message' => $e->getMessage(),
            ]);

            return $this->reject('toasts.auth.sso_error');
        }

        Log::debug('SSO login data', [
            'provider' => $provider,
            'userData' => $ssoUser,
        ]);

        try {
            $user = $this->socialLogin->resolveUser(
                $provider,
                $ssoUser,
                GuestLocale::fromRequest($request),
            );
        } catch (UnverifiedSocialEmailException $e) {
            return $this->reject('toasts.auth.sso_unverified_email');
        }

        return $this->login($user);
    }

    private function ensureSupportedProvider(string $provider): void
    {
        if (! in_array($provider, config('auth.sso.supported'), true)) {
            abort(403, 'Provider not supported.');
        }
    }

    private function login(User $user): RedirectResponse
    {
        Auth::login($user);

        request()->session()->regenerate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.auth.welcome_back')]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function reject(string $messageKey): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => __($messageKey)]);

        return redirect()->route('login');
    }
}

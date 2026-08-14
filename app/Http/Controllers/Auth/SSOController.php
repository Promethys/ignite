<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\SocialLoginService;
use App\Services\Auth\UnverifiedSocialEmailException;
use App\Support\GuestLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Socialite;

class SSOController extends Controller
{
    public function __construct(private readonly SocialLoginService $socialLogin) {}

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        $authenticatedUser = auth()->user();

        if (
            $authenticatedUser
            && $authenticatedUser->socialAccounts()->where('provider', $provider)->exists()
        ) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('toasts.auth.sso_already_connected', ['provider' => $this->providerLabel($provider)]),
            ]);

            return redirect()->back();
        }

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

        $authenticatedUser = auth()->user();

        if ($authenticatedUser) {
            return $this->handleConnection($request, $provider, $ssoUser);
        } else {
            return $this->handleLogin($request, $provider, $ssoUser);
        }
    }

    public function logout(Request $request, string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        $user = $request->user();
        $userIsConnectedToProvider = $user->socialAccounts()
            ->where('provider', $provider)
            ->exists();

        if (! $userIsConnectedToProvider) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('toasts.auth.sso_not_connected', ['provider' => $this->providerLabel($provider)]),
            ]);

            return redirect()->back();
        }

        if ($user->socialAccounts()->count() === 1 && ! $user->has_password) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('toasts.auth.sso_last_credential', ['provider' => $this->providerLabel($provider)]),
            ]);

            return redirect()->back();
        }

        $user->socialAccounts()
            ->where('provider', $provider)
            ->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('toasts.auth.sso_disconnected', ['provider' => $this->providerLabel($provider)]),
        ]);

        return redirect()->route('connected-accounts.index');
    }

    private function handleLogin(Request $request, string $provider, SocialiteUser $ssoUser)
    {
        try {
            $user = $this->socialLogin->resolveUser(
                $provider,
                $ssoUser,
                GuestLocale::fromRequest($request),
            );
        } catch (UnverifiedSocialEmailException $e) {
            return $this->reject('toasts.auth.sso_unverified_email');
        }

        if (Features::enabled(Features::twoFactorAuthentication()) && $user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            return to_route('two-factor.login');
        }

        return $this->login($user);
    }

    private function handleConnection(Request $request, string $provider, SocialiteUser $ssoUser)
    {
        $user = auth()->user();
        $existingSocialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $ssoUser->getId())
            ->where('user_id', '!=', $user->id)
            ->first();

        if ($existingSocialAccount) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('toasts.auth.sso_claimed_by_another_account', ['provider' => $this->providerLabel($provider)]),
            ]);

            return redirect()->back();
        }

        $this->socialLogin->linkProvider($user, $provider, $ssoUser);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('toasts.auth.sso_connected', ['provider' => $this->providerLabel($provider)]),
        ]);

        return redirect()->route('connected-accounts.index');
    }

    private function ensureSupportedProvider(string $provider): void
    {
        if (! in_array($provider, config('auth.sso.supported'), true)) {
            abort(403, 'Provider not supported.');
        }
    }

    private function providerLabel(string $provider): string
    {
        return config("auth.sso.labels.$provider", ucfirst($provider));
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

<?php

namespace App\Services\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialLoginService
{
    public function resolveUser(string $provider, SocialiteUser $ssoUser, ?string $locale = null): User
    {
        $account = SocialAccount::where('provider', $provider)
            ->where('provider_id', $ssoUser->getId())
            ->first();

        if ($account) {
            $this->syncTokens($account, $ssoUser);

            return $account->user;
        }

        $user = User::where('email', $ssoUser->getEmail())->first();

        if ($user) {
            if (! $this->emailVerified($ssoUser)) {
                throw new UnverifiedSocialEmailException;
            }

            $this->linkProvider($user, $provider, $ssoUser);

            return $user;
        }

        $user = $this->createUser($ssoUser, $locale);
        $this->linkProvider($user, $provider, $ssoUser);

        return $user;
    }

    private function emailVerified(SocialiteUser $ssoUser): bool
    {
        $raw = $ssoUser->getRaw() ?? [];

        return (bool) ($raw['verified_email'] ?? $raw['email_verified'] ?? false);
    }

    private function syncTokens(SocialAccount $account, SocialiteUser $ssoUser): void
    {
        $account->update([
            'token' => $ssoUser->token,
            'refresh_token' => $ssoUser->refreshToken,
            'provider_data' => $ssoUser->getRaw(),
        ]);
    }

    private function linkProvider(User $user, string $provider, SocialiteUser $ssoUser): SocialAccount
    {
        return $user->socialAccounts()->updateOrCreate(
            ['provider' => $provider],
            [
                'provider_id' => $ssoUser->getId(),
                'token' => $ssoUser->token,
                'refresh_token' => $ssoUser->refreshToken,
                'provider_data' => $ssoUser->getRaw(),
            ],
        );
    }

    private function createUser(SocialiteUser $ssoUser, ?string $locale): User
    {
        $user = DB::transaction(function () use ($ssoUser, $locale) {
            return User::create([
                'name' => $ssoUser->getName() ?: $ssoUser->getNickname(),
                'email' => $ssoUser->getEmail(),
                'password' => null,
                'locale' => $locale ?? config('app.fallback_locale'),
            ]);
        });

        $user->markEmailAsVerified();

        event(new Registered($user));

        return $user;
    }
}

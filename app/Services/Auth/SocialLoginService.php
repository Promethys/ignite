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
        return DB::transaction(function () use ($provider, $ssoUser, $locale) {
            $account = SocialAccount::where('provider', $provider)
                ->where('provider_id', $ssoUser->getId())
                ->first();

            if ($account) {
                $this->syncProviderData($account, $ssoUser);

                return $account->user;
            }

            $user = User::where('email', $ssoUser->getEmail())->first();

            if ($user) {
                if (! $this->emailVerified($ssoUser, $provider)) {
                    throw new UnverifiedSocialEmailException;
                }

                $this->linkProvider($user, $provider, $ssoUser);

                return $user;
            }

            $user = $this->createUser($ssoUser, $locale);
            $this->linkProvider($user, $provider, $ssoUser);

            return $user;
        });
    }

    public function linkProvider(User $user, string $provider, SocialiteUser $ssoUser): SocialAccount
    {
        return $user->socialAccounts()->updateOrCreate(
            ['provider' => $provider],
            [
                'provider_id' => $ssoUser->getId(),
                'provider_data' => $ssoUser->getRaw(),
            ],
        );
    }

    private function emailVerified(SocialiteUser $ssoUser, string $provider): bool
    {
        if (config("services.$provider.all_emails_verified") === true) {
            return true;
        }

        $raw = $ssoUser->getRaw() ?? [];

        return (bool) ($raw['verified_email'] ?? $raw['email_verified'] ?? false);
    }

    private function syncProviderData(SocialAccount $account, SocialiteUser $ssoUser): void
    {
        $account->update(['provider_data' => $ssoUser->getRaw()]);
    }

    private function createUser(SocialiteUser $ssoUser, ?string $locale): User
    {
        $user = User::create([
            'name' => $ssoUser->getName() ?: $ssoUser->getNickname(),
            'email' => $ssoUser->getEmail(),
            'password' => null,
            'locale' => $locale ?? config('app.fallback_locale'),
        ]);

        $user->markEmailAsVerified();

        event(new Registered($user));

        return $user;
    }
}

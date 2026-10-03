<?php

namespace App\Services\Assistant;

use App\Enums\AssistantKeyCheck;
use App\Enums\AssistantProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class AssistantKeyChecker
{
    private const TIMEOUT_SECONDS = 8;

    /**
     * Ask the provider whether it accepts the key, without spending any tokens.
     */
    public function check(AssistantProvider $provider, string $apiKey): AssistantKeyCheck
    {
        try {
            $response = $this->authenticated($provider, $apiKey)->get($this->checkUrl($provider));
        } catch (ConnectionException) {
            return AssistantKeyCheck::Unreachable;
        }

        return match (true) {
            $response->successful() => AssistantKeyCheck::Valid,
            in_array($response->status(), [400, 401, 403], true) => AssistantKeyCheck::Invalid,
            default => AssistantKeyCheck::Unreachable,
        };
    }

    private function authenticated(AssistantProvider $provider, string $apiKey): PendingRequest
    {
        $request = Http::timeout(self::TIMEOUT_SECONDS)->acceptJson();

        return match ($provider) {
            AssistantProvider::Gemini => $request->withHeaders(['x-goog-api-key' => $apiKey]),
            AssistantProvider::Anthropic => $request->withToken($apiKey)->withHeaders(['anthropic-version' => config('ai.providers.anthropic.version')]),
            default => $request->withToken($apiKey),
        };
    }

    private function checkUrl(AssistantProvider $provider): string
    {
        return rtrim(config("ai.providers.{$provider->value}.url"), '/').($provider === AssistantProvider::OpenRouter ? '/key' : '/models');
    }
}

<?php

namespace App\Services\Assistant;

use App\Models\User;

class AssistantKeyResolver
{
    private const DRIVERS_WITHOUT_A_KEY = ['ollama', 'openai-compatible', 'bedrock'];

    /**
     * The provider the assistant should use for this user: their default key, else the instance configuration.
     */
    public function resolve(User $user): ?AssistantConnection
    {
        $assistantKey = $user->defaultAssistantKey;

        if ($assistantKey !== null) {
            return new AssistantConnection(
                [...config("ai.providers.{$assistantKey->provider->value}"), 'key' => $assistantKey->api_key],
                $assistantKey->model,
            );
        }

        $instanceConfiguration = $this->instanceConfiguration();

        return $instanceConfiguration === null ? null : new AssistantConnection($instanceConfiguration);
    }

    /**
     * Whether the assistant can run for this user, without reading any key.
     */
    public function isAvailableFor(User $user): bool
    {
        return $this->instanceIsConfigured()
            || $user->assistantKeys()->where('is_default', true)->exists();
    }

    public function instanceIsConfigured(): bool
    {
        return $this->instanceConfiguration() !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function instanceConfiguration(): ?array
    {
        $defaultProvider = config('ai.default');

        if (blank($defaultProvider)) {
            return null;
        }

        $configuration = config("ai.providers.{$defaultProvider}");

        if (! is_array($configuration)) {
            return null;
        }

        $needsNoKey = in_array($configuration['driver'] ?? null, self::DRIVERS_WITHOUT_A_KEY, true);

        return $needsNoKey || filled($configuration['key'] ?? null) ? $configuration : null;
    }
}

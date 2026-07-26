<?php

namespace App\Services\Mcp;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DestructiveConfirmations
{
    public function issue(User $actor, string $operation, array $target): string
    {
        $token = Str::random(40);
        $key = $this->buildKey($token);

        Cache::put(
            $key,
            [
                'actor_id' => $actor->id,
                'operation' => $operation,
                'target' => $target
            ],
            120
        );

        return $token;
    }

    public function consume(User $actor, string $token, string $operation, array $target): bool
    {
        $key = $this->buildKey($token);
        $cached = Cache::pull($key);

        if(
            $cached === null
            || !is_array($cached)
        ) {
            return false;
        }

        if (
            $cached['actor_id'] !== $actor->id
            || $cached['operation'] !== $operation
            || $cached['target'] !== $target
        ) {
            return false;
        }

        return true;
    }

    protected function buildKey(string $token): string
    {
        return "mcp:destructive-confirmation:{$token}";
    }
}

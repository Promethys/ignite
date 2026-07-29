<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Tool;

abstract class IgniteTool extends Tool
{
    abstract protected function requiredAbility(): string;   // 'read' | 'write' | 'delete'

    public function shouldRegister(Request $request): bool
    {
        $user = $this->actor($request);

        if (! $user) {
            return false;
        }

        return $user->currentAccessToken() === null
            || $user->tokenCan($this->requiredAbility());
    }

    /**
     * Derive the human-readable title from the snake_case name so it stays
     * in sync without per-tool #[Title] attributes. e.g. "list_goals" -> "List Goals".
     */
    public function title(): string
    {
        return Str::headline($this->name());
    }

    /**
     * Validate the request against its normalized arguments.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function validateTrimmed(Request $request, array $rules): array
    {
        return validator($this->normalizedArguments($request), $rules)->validate();
    }

    /**
     * The request's arguments with strings trimmed and blank ones dropped.
     *
     * HTTP requests reach the validator through the `TrimStrings` and
     * `ConvertEmptyStringsToNull` middleware; MCP requests have no middleware
     * stack. Without this, a whitespace-only string is written verbatim:
     * validation cannot catch it, because Laravel skips every non-implicit
     * rule for a value that trims to empty.
     *
     * A blank string is dropped rather than nulled, so it reads as "the field
     * was not supplied" and a partial update leaves the stored value alone.
     *
     * @return array<string, mixed>
     */
    protected function normalizedArguments(Request $request): array
    {
        return collect($request->all())
            ->map(fn (mixed $value): mixed => is_string($value) ? trim($value) : $value)
            ->reject(fn (mixed $value): bool => $value === '')
            ->all();
    }

    protected function actor(Request $request): ?User
    {
        $user = $request->user();

        if ($user instanceof User) {
            return $user;
        }

        $localUser = config('mcp.local_user');

        if ($localUser !== null) {
            return User::whereRaw('lower(email) = ?', [strtolower($localUser)])
                ->first();
        }

        return null;
    }
}

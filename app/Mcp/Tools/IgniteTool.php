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

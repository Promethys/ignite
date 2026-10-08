<?php

namespace App\Ai;

use App\Ai\Tools\ConfirmedDeletion;
use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\DeleteCategoryTool;
use App\Mcp\Tools\DeleteEntryTool;
use App\Mcp\Tools\DeleteGoalTool;
use App\Mcp\Tools\IgniteTool;
use Laravel\Mcp\Request;

class AssistantTools
{
    private const DESTRUCTIVE_TOOLS = [
        DeleteCategoryTool::class,
        DeleteEntryTool::class,
        DeleteGoalTool::class,
    ];

    /**
     * The MCP tools the current user may use, with deletions gated behind the user's approval.
     *
     * @return list<IgniteTool|ConfirmedDeletion>
     */
    public function all(): array
    {
        return collect(IgniteServer::TOOLS)
            ->map(fn (string $toolClass): IgniteTool => app($toolClass))
            ->filter(fn (IgniteTool $tool): bool => $tool->shouldRegister(new Request))
            ->map(fn (IgniteTool $tool): IgniteTool|ConfirmedDeletion => in_array($tool::class, self::DESTRUCTIVE_TOOLS, true)
                ? new ConfirmedDeletion($tool)
                : $tool)
            ->values()
            ->all();
    }
}

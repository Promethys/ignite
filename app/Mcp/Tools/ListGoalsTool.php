<?php

namespace App\Mcp\Tools;

use App\Http\Resources\GoalResource;
use App\Services\Goals\GoalService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Name('list_goals')]
#[Description('List the authenticated user\'s goals with their streak, status, type, progress, and milestone summary. Supports optional filters to narrow the list; an unfiltered call returns every goal.')]
class ListGoalsTool extends IgniteTool
{
    public function __construct(
        private readonly GoalService $goalService
    ) {}

    protected function requiredAbility(): string
    {
        return 'read';
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'status' => 'nullable|in:not_started,in_progress,completed,paused,abandoned',
            'type' => 'nullable|in:simple,quantifiable,recurring,multi_step',
            'category_id' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:191',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $result = $this->goalService->listForUser($this->actor($request), [
            'status' => $validated['status'] ?? null,
            'type' => $validated['type'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'search' => $validated['search'] ?? null,
            'limit' => $validated['limit'] ?? null,
        ]);

        $text = $result['goals']->count() < $result['total']
            ? 'Retrieved '.$result['goals']->count().' of '.$result['total'].' goals.'
            : 'Retrieved '.$result['goals']->count().' goals.';

        return Response::make(
            Response::text($text)
        )->withStructuredContent([
            'goals' => GoalResource::collection($result['goals'])->resolve(),
            'total' => $result['total'],
            'limit' => $result['limit'],
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->description('Filter by goal status.')
                ->enum(['not_started', 'in_progress', 'completed', 'paused', 'abandoned'])
                ->nullable(),
            'type' => $schema->string()
                ->description('Filter by goal type.')
                ->enum(['simple', 'quantifiable', 'recurring', 'multi_step'])
                ->nullable(),
            'category_id' => $schema->integer()
                ->description('Filter by category id.')
                ->min(1)
                ->nullable(),
            'search' => $schema->string()
                ->description('Case-insensitive search over goal title and description.')
                ->max(191)
                ->nullable(),
            'limit' => $schema->integer()
                ->description('Maximum number of goals to return. Default returns all; max 100.')
                ->min(1)
                ->max(100)
                ->nullable(),
        ];
    }
}

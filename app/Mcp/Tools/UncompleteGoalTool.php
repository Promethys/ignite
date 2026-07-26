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

#[Name('uncomplete_goal')]
#[Description('Revert a completed goal back to an active status and clear its completion time. The new status must be an active one (not completed).')]
class UncompleteGoalTool extends IgniteTool
{
    public function __construct(
        private readonly GoalService $goalService
    ) {}

    protected function requiredAbility(): string
    {
        return 'write';
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'goal_id' => 'required|integer|exists:goals,id',
            'status' => 'required|in:not_started,in_progress,paused,abandoned',
        ]);

        $user = $request->user();
        $goal = $this->goalService->find($user, $validated['goal_id']);

        $this->goalService->uncomplete($user, $goal, $validated['status']);

        return Response::make(
            Response::text('Reverted the goal "'.$goal->title.'" to '.$validated['status'].'.')
        )->withStructuredContent((new GoalResource($goal->fresh()))->resolve());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'goal_id' => $schema->integer()
                ->description('The ID of the goal to revert.')
                ->required(),
            'status' => $schema->string()
                ->description('The active status to restore the goal to.')
                ->enum(['not_started', 'in_progress', 'paused', 'abandoned'])
                ->required(),
        ];
    }
}

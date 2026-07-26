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

#[Name('set_goal_status')]
#[Description('Change the status of one of the user\'s goals.')]
class SetGoalStatusTool extends IgniteTool
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
            'status' => 'required|in:not_started,in_progress,completed,paused,abandoned',
        ]);

        $user = $request->user();
        $goal = $this->goalService->find($user, $validated['goal_id']);

        $this->goalService->setStatus($user, $goal, $validated['status']);

        return Response::make(
            Response::text('Set the goal "'.$goal->title.'" to '.$validated['status'].'.')
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
                ->description('The ID of the goal whose status should change.')
                ->required(),
            'status' => $schema->string()
                ->description('The new status.')
                ->enum(['not_started', 'in_progress', 'completed', 'paused', 'abandoned'])
                ->required(),
        ];
    }
}

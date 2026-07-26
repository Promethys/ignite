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

#[Name('complete_goal')]
#[Description('Mark one of the user\'s goals as completed. Sets the status to completed and records the completion time.')]
class CompleteGoalTool extends IgniteTool
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
        ]);

        $user = $request->user();
        $goal = $this->goalService->find($user, $validated['goal_id']);

        $this->goalService->complete($user, $goal);

        return Response::make(
            Response::text("Completed the goal '{$goal->title}'.")
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
                ->description('The ID of the goal to complete.')
                ->required(),
        ];
    }
}

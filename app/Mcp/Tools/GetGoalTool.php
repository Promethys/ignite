<?php

namespace App\Mcp\Tools;

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
#[Name('get_goal')]
#[Description('Get a single goal by its id, including its recent progress entries, milestones, and streak.')]
class GetGoalTool extends IgniteTool
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
            'goal_id' => 'integer|required|exists:goals,id',
        ]);

        $user = $request->user();

        $goal = $this->goalService->find($user, $validated['goal_id']);

        return Response::structured($goal->attributesToArray());
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
                ->description('The ID of the goal to retrieve')
                ->required(),
        ];
    }
}

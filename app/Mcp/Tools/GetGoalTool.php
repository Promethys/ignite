<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Services\Goals\GoalService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Get a single goal by its id, including its recent progress entries, milestones, and streak.')]
class GetGoalTool extends Tool
{
    public function __construct(
        private readonly GoalService $goalService
    ) {}

    /**
     * Determine if the tool should be registered.
     */
    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->currentAccessToken() === null || $user->tokenCan('read');
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'goal_id' => 'integer|required',
        ]);

        $user = $request->user();

        $goal = $this->goalService->find($user, $validated['goal_id']);

        return Response::structured($goal->toArray());
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

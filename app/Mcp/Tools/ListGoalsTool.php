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
#[Name('list_goals')]
#[Description('List all of the authenticated user\'s goals with their streak, status, type, and progress.')]
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
        $user = $request->user();

        $goals = $this->goalService->listForUser($user);

        return Response::make(
            Response::text('The user has '.$goals->count().' goals.')
        )->withStructuredContent($goals->toArray());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            //
        ];
    }
}

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
#[Description('List all of the authenticated user\'s goals with their streak, status, type, and progress.')]
class ListGoalsTool extends Tool
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

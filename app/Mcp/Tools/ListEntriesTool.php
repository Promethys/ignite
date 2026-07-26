<?php

namespace App\Mcp\Tools;

use App\Http\Resources\GoalEntryResource;
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
#[Name('list_entries')]
#[Description('List the progress entries logged against one of the user\'s goals, newest first.')]
class ListEntriesTool extends IgniteTool
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
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'goal_id' => 'required|integer|exists:goals,id',
        ]);

        $goal = $this->goalService->find($request->user(), $validated['goal_id']);
        $entries = $goal->entries;

        if ($entries->isEmpty()) {
            return Response::text('This goal does not have any entry');
        }

        return Response::make(
            Response::text('Retrieved the goal\'s progress entries.')
        )->withStructuredContent(['entries' => GoalEntryResource::collection($goal->entries)->resolve()]);
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
                ->description('The ID of the goal whose entries should be listed.')
                ->required(),
        ];
    }
}

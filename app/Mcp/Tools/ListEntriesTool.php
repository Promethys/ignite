<?php

namespace App\Mcp\Tools;

use App\Http\Resources\GoalEntryResource;
use App\Models\Goal;
use App\Services\Goals\GoalEntryService;
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
#[Description('List the progress entries logged against one of the user\'s goals, newest first. Returns at most `limit` entries (default 50, max 200) along with a `total` count; when `total` exceeds the number returned, more entries exist than were sent.')]
class ListEntriesTool extends IgniteTool
{
    public function __construct(
        private readonly GoalEntryService $goalEntryService
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
            'search' => 'nullable|string|max:191',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $goal = Goal::findOrFail($validated['goal_id']);

        $result = $this->goalEntryService->listEntries($this->actor($request), $goal, [
            'search' => $validated['search'] ?? null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'limit' => $validated['limit'] ?? null,
        ]);

        if ($result['entries']->isEmpty()) {
            return Response::text('This goal does not have any matching entry.');
        }

        return Response::make(
            Response::text('Retrieved '.$result['entries']->count().' of '.$result['total'].' progress entries.')
        )->withStructuredContent([
            'entries' => GoalEntryResource::collection($result['entries'])->resolve(),
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
            'goal_id' => $schema->integer()
                ->description('The ID of the goal whose entries should be listed.')
                ->required(),
            'search' => $schema->string()
                ->description('Optional case-insensitive filter on the entry note.')
                ->max(191)
                ->nullable(),
            'from' => $schema->string()
                ->description('Optional inclusive lower bound on `entry_date` as YYYY-MM-DD.')
                ->format('date')
                ->nullable(),
            'to' => $schema->string()
                ->description('Optional inclusive upper bound on `entry_date` as YYYY-MM-DD. Must be on or after `from`.')
                ->format('date')
                ->nullable(),
            'limit' => $schema->integer()
                ->description('Maximum number of entries to return. Default 50, max 200.')
                ->min(1)
                ->max(200)
                ->nullable(),
        ];
    }
}

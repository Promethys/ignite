<?php

namespace App\Mcp\Tools;

use App\Http\Resources\GoalEntryResource;
use App\Services\Goals\GoalEntryService;
use App\Services\Goals\GoalService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('log_progress')]
#[Description('Log progress on one of the user\'s quantifiable or simple goals by shifting its current value by the given increment, and create a progress entry recording the change.')]
class LogProgressTool extends IgniteTool
{
    public function __construct(
        private readonly GoalService $goalService,
        private readonly GoalEntryService $goalEntryService
    ) {}

    protected function requiredAbility(): string
    {
        return 'write';
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $this->validateTrimmed($request, [
            'goal_id' => 'required|integer|exists:goals,id',
            'increment' => 'required|numeric',
            'note' => 'nullable|string|max:500',
        ]);

        $user = $this->actor($request);
        $goal = $this->goalService->find($user, $validated['goal_id']);

        $entry = $this->goalEntryService->logProgress(
            $user,
            $goal,
            $validated['increment'],
            $validated['note'] ?? null
        );

        return Response::make(
            Response::text('Logged progress on the goal.')
        )->withStructuredContent((new GoalEntryResource($entry))->resolve());
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
                ->description('The ID of the goal to add progress to.')
                ->required(),
            'increment' => $schema->number()
                ->description('The amount to add to (or subtract from) the goal\'s current value. Positive advances an ascending goal; use a negative value to reduce progress.')
                ->required(),
            'note' => $schema->string()
                ->description('An optional note attached to the progress entry, up to 500 characters.')
                ->max(500)
                ->nullable(),
        ];
    }
}

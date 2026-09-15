<?php

namespace App\Mcp\Tools;

use App\Rules\GoalEntryRules;
use App\Services\Goals\GoalEntryService;
use App\Services\Goals\GoalService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('log_progress_batch')]
#[Description('Log many progress entries on one of the user\'s quantifiable or simple goals in a single call, such as backfilling history from an export. Every entry is validated before anything is written, and the batch is saved all or nothing: if any entry is rejected, none are saved and the errors name the failing entries by index. Send entries in batches of up to 200; for longer histories, send several batches in date order. If a call fails or times out, call list_entries before retrying, because a batch that was saved would otherwise be logged twice.')]
class LogProgressBatchTool extends IgniteTool
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
        $goalValidated = $request->validate([
            'goal_id' => ['required', 'integer', 'exists:goals,id'],
        ]);

        $user = $this->actor($request);
        $goal = $this->goalService->find($user, $goalValidated['goal_id']);

        $entriesRules = [
            'entries' => ['required', 'array', 'min:1', 'max:200'],
            'entries.*' => ['array:increment,entry_date,note'],
        ];

        foreach (GoalEntryRules::progressRules($goal) as $key => $rules) {
            $entriesRules["entries.*.$key"] = $rules;
        }

        $validated = $this->validateTrimmed($request, $entriesRules);
        $responseData = $this->goalEntryService->logProgressBatch($user, $goal, $validated['entries']);

        return Response::make(
            Response::text('Logged progress batch on the goal.')
        )->withStructuredContent($responseData);
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
            'entries' => $schema->array()
                ->description('The progress entries to log, between 1 and 200. Each one shifts the goal\'s current value by its increment, applied in date order.')
                ->items($schema->object([
                    'increment' => $schema->number()
                        ->description('The amount to add to (or subtract from) the goal\'s current value. Use a negative value to reduce progress.')
                        ->required(),
                    'entry_date' => $schema->string()
                        ->description('An optional calendar date for the entry as YYYY-MM-DD, defaulting to today. Must be on or before today, and may predate the goal\'s start date.')
                        ->format('date')
                        ->nullable(),
                    'note' => $schema->string()
                        ->description('An optional note attached to the entry, up to 2000 characters.')
                        ->max(2000)
                        ->nullable(),
                ]))
                ->required()
                ->min(1)
                ->max(200),
        ];
    }
}

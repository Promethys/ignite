<?php

namespace App\Mcp\Tools;

use App\Http\Resources\GoalEntryResource;
use App\Services\Goals\GoalEntryService;
use App\Services\Goals\GoalService;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('check_in')]
#[Description('Record a dated check-in on one of the user\'s recurring (habit) goals. Each recurrence period (daily, weekly, monthly, or annually) accepts a single check-in, and this never changes the goal\'s current value.')]
class CheckInTool extends IgniteTool
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
    public function handle(Request $request): ResponseFactory
    {
        $goalValidated = $request->validate([
            'goal_id' => ['required', 'integer', 'exists:goals,id'],
        ]);

        $user = $this->actor($request);
        $goal = $this->goalService->find($user, $goalValidated['goal_id']);
        $timezone = $goal->user?->timezone ?? config('app.timezone');
        $today = Carbon::now()->timezone($timezone)->toDateString();

        $rules = [
            'entry_date' => ['required', 'date', "before_or_equal:{$today}"],
            'note' => ['nullable', 'string', 'max:500'],
        ];

        if ($goal->start_date) {
            $rules['entry_date'][] = 'after_or_equal:'.$goal->start_date->toDateString();
        }

        $validated = $request->validate($rules);

        $entry = $this->goalEntryService->recordCheckIn(
            $user,
            $goal,
            $validated['entry_date'],
            $validated['note'] ?? null
        );

        return Response::make(
            Response::text('Recorded a check-in for the recurring goal.')
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
                ->description('The ID of the recurring goal to check in on.')
                ->required(),
            'entry_date' => $schema->string()
                ->description('The calendar date of the check-in as YYYY-MM-DD. Must be on or before today, and on or after the goal\'s start date if it has one.')
                ->format('date')
                ->max(255)
                ->required(),
            'note' => $schema->string()
                ->description('An optional note attached to the check-in, up to 500 characters.')
                ->max(500)
                ->nullable(),
        ];
    }
}

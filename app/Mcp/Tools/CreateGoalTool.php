<?php

namespace App\Mcp\Tools;

use App\Http\Resources\GoalResource;
use App\Rules\GoalRules;
use App\Services\Goals\GoalService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('create_goal')]
#[Description('Create a new goal owned by the authenticated user. The owner is always the token holder; a client-supplied user id is ignored.')]
class CreateGoalTool extends IgniteTool
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
     *
     * Only `title` and `type` carry user intent that must be stated; the
     * operational fields (status, priority, points, visibility, etc.) have
     * sensible defaults applied server-side, so a caller can create a goal
     * from minimal input. `target_value` remains required for a quantifiable
     * goal, since no default can stand in for a real target.
     */
    public function handle(Request $request): ResponseFactory
    {
        $rules = GoalRules::partialRules();

        $rules['title'] = 'required|string|max:255';
        $rules['type'] = 'required|in:simple,quantifiable,recurring,multi_step';

        // A quantifiable goal cannot exist without a target to aim at.
        if ($request->get('type') === 'quantifiable') {
            $rules['target_value'] = 'required|numeric';
        }

        $validated = $request->validate($rules);

        $goal = $this->goalService->create($request->user(), $validated);

        return Response::make(
            Response::text('Created the goal "'.$goal->title.'".')
        )->withStructuredContent((new GoalResource($goal))->resolve());
    }

    /**
     * Get the tool's input schema.
     *
     * Only `title` and `type` are required; every operational field defaults
     * server-side when omitted.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('The goal title.')->max(255)->required(),
            'type' => $schema->string()->description('The goal type.')->enum(['simple', 'quantifiable', 'recurring', 'multi_step'])->required(),
            'direction' => $schema->string()->description('Whether progress ascends toward or descends to the target. Defaults to ascending.')->enum(['ascending', 'descending'])->nullable(),
            'current_value' => $schema->number()->description('The starting value of the goal. Defaults to 0.')->nullable(),
            'target_value' => $schema->number()->description('The target value. Required for quantifiable goals.')->nullable(),
            'unit' => $schema->string()->description('The unit label for the value, e.g. "km" or "books".')->max(50)->nullable(),
            'status' => $schema->string()->description('The goal status. Defaults to not_started.')->enum(['not_started', 'in_progress', 'completed', 'paused', 'abandoned'])->nullable(),
            'priority' => $schema->string()->description('The goal priority. Defaults to medium.')->enum(['low', 'medium', 'high'])->nullable(),
            'recurrence' => $schema->string()->description('The check-in cadence for a recurring goal.')->enum(['daily', 'weekly', 'monthly', 'annually'])->nullable(),
            'polarity' => $schema->string()->description('Whether the habit is positive (build) or negative (quit). Defaults to positive.')->enum(['positive', 'negative'])->nullable(),
            'points' => $schema->integer()->description('Gamification points awarded. Defaults to 0.')->min(0)->nullable(),
            'is_public' => $schema->boolean()->description('Whether the goal is visible to others. Defaults to false.')->nullable(),
            'description' => $schema->string()->description('An optional longer description of the goal.')->nullable(),
            'icon' => $schema->string()->description('An optional emoji or icon.')->max(50)->nullable(),
            'category_id' => $schema->integer()->description('An optional category id.')->nullable(),
            'start_date' => $schema->string()->description('An optional start date as YYYY-MM-DD.')->format('date')->nullable(),
            'deadline' => $schema->string()->description('An optional deadline as YYYY-MM-DD. Must be on or after the start date.')->format('date')->nullable(),
            'order' => $schema->integer()->description('Display order; if omitted the server assigns the next position.')->nullable(),
        ];
    }
}

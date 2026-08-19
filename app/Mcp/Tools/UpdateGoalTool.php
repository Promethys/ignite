<?php

namespace App\Mcp\Tools;

use App\Http\Resources\GoalResource;
use App\Rules\GoalRules;
use App\Services\Goals\GoalService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('update_goal')]
#[Description('Update an existing goal owned by the authenticated user. Only the fields you want to change need to be supplied; omitted fields keep their current value. Always provide `goal_id`; every other parameter is optional.')]
class UpdateGoalTool extends IgniteTool
{
    public function __construct(
        private readonly GoalService $goalService
    ) {}

    protected function requiredAbility(): string
    {
        return 'write';
    }

    /**
     * Partial update: only the supplied keys are written back.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $goalId = $request->validate([
            'goal_id' => 'required|integer|exists:goals,id',
        ])['goal_id'];

        $user = $this->actor($request);
        $goal = $this->goalService->find($user, $goalId);

        $provided = $this->normalizedArguments($request);
        unset($provided['goal_id']);

        if ($provided === []) {
            return Response::error('No fields were provided to update.');
        }

        $rules = GoalRules::partialRules($user->id);

        $merged = array_merge($goal->only(array_keys($rules)), $provided);

        $validated = Validator::validate($merged, $rules);

        $updateAttributes = array_intersect_key($validated, $provided);

        $updated = $this->goalService->update($user, $goal, $updateAttributes)
            ->load('category', 'milestones');

        return Response::make(
            Response::text('Updated the goal "'.$updated->title.'".')
        )
            ->withStructuredContent((new GoalResource($updated))
                ->resolve());
    }

    /**
     * `goal_id` is the only required field.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'goal_id' => $schema->integer()
                ->description('The ID of the goal to update.')
                ->required(),
            'title' => $schema->string()
                ->description('The goal title.')
                ->max(255)
                ->nullable(),
            'type' => $schema->string()
                ->description('The goal type.')
                ->enum(['simple', 'quantifiable', 'recurring', 'multi_step'])
                ->nullable(),
            'direction' => $schema->string()
                ->description('Whether progress ascends toward or descends to the target.')
                ->enum(['ascending', 'descending'])
                ->nullable(),
            'current_value' => $schema->number()
                ->description('The current value of the goal.')
                ->nullable(),
            'target_value' => $schema->number()
                ->description('The target value. Required for quantifiable goals.')
                ->nullable(),
            'unit' => $schema->string()
                ->description('The unit label for the value.')
                ->max(50)
                ->nullable(),
            'status' => $schema->string()
                ->description('The goal status.')
                ->enum(['not_started', 'in_progress', 'completed', 'paused', 'abandoned'])
                ->nullable(),
            'priority' => $schema->string()
                ->description('The goal priority.')
                ->enum(['low', 'medium', 'high'])
                ->nullable(),
            'recurrence' => $schema->string()
                ->description('The check-in cadence for a recurring goal.')
                ->enum(['daily', 'weekly', 'monthly', 'annually'])
                ->nullable(),
            'polarity' => $schema->string()
                ->description('Whether the habit is positive (build) or negative (quit).')
                ->enum(['positive', 'negative'])
                ->nullable(),
            'points' => $schema->integer()
                ->description('Gamification points awarded.')
                ->min(0)
                ->nullable(),
            'is_public' => $schema->boolean()
                ->description('Whether the goal is visible to others.')
                ->nullable(),
            'description' => $schema->string()
                ->description('An optional longer description of the goal.')
                ->nullable(),
            'icon' => $schema->string()
                ->description('An optional emoji or icon.')
                ->max(50)
                ->nullable(),
            'category_id' => $schema->integer()
                ->description('An optional category id.')
                ->nullable(),
            'start_date' => $schema->string()
                ->description('An optional start date as YYYY-MM-DD.')
                ->format('date')
                ->nullable(),
            'deadline' => $schema->string()
                ->description('An optional deadline as YYYY-MM-DD. Must be on or after the goal\'s start date.')
                ->format('date')
                ->nullable(),
            'completed_at' => $schema->string()
                ->description('An optional completion timestamp. Must be on or after the goal\'s start date.')
                ->format('date')
                ->nullable(),
            'order' => $schema->integer()
                ->description('Display order.')
                ->nullable(),
        ];
    }
}

<?php

namespace App\Mcp\Tools;

use App\Http\Resources\MilestoneResource;
use App\Rules\GoalRules;
use App\Rules\MilestoneRules;
use App\Services\Goals\GoalService;
use App\Services\Goals\MilestoneService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('add_milestone')]
#[Description('Add a milestone to one of the user\'s goals. A milestone is a named checkpoint on the way to the goal; on a multi_step goal each milestone is one step, and completing steps is what advances that goal. A step is appended after the goal\'s existing steps; milestones on a quantifiable goal are kept in target value order.')]
class AddMilestoneTool extends IgniteTool
{
    public function __construct(
        private readonly GoalService $goalService,
        private readonly MilestoneService $milestoneService,
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
        $user = $this->actor($request);

        $goalId = $this->validateTrimmed($request, [
            'goal_id' => ['required', 'integer', GoalRules::ownerIdRule($user)],
        ])['goal_id'];

        $goal = $this->goalService->find($user, $goalId);

        $validated = $this->validateTrimmed($request, MilestoneRules::rules($goal));

        $milestone = $this->milestoneService->add(
            $user,
            $goal,
            $validated
        );

        return Response::make(
            Response::text("Recorded a milestone for the goal '{$goal->title}'.")
        )->withStructuredContent((new MilestoneResource($milestone))->resolve());
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
                ->description('The ID of the goal to add the milestone to.')
                ->required(),
            'title' => $schema->string()
                ->description('The milestone title, e.g. "Finish chapter 3" or "Run 10 km without stopping".')
                ->max(255)
                ->required(),
            'target_value' => $schema->number()
                ->description('For a quantifiable goal, the goal value at which this milestone counts as reached.')
                ->nullable(),
            'description' => $schema->string()
                ->description('An optional longer description of the milestone, up to 2000 characters.')
                ->max(2000)
                ->nullable(),
            'deadline' => $schema->string()
                ->description('An optional due date (YYYY-MM-DD), within the goal\'s start date and deadline when those are set.')
                ->nullable(),
            'points_reward' => $schema->number()
                ->description('Optional gamification points awarded when the milestone is completed.')
                ->nullable(),
        ];
    }
}

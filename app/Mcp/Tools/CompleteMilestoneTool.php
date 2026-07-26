<?php

namespace App\Mcp\Tools;

use App\Http\Resources\MilestoneResource;
use App\Models\Milestone;
use App\Services\Goals\MilestoneService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('complete_milestone')]
#[Description('Mark one of the user\'s milestones as completed. On a multi_step goal this checks off the step and advances the goal\'s progress.')]
class CompleteMilestoneTool extends IgniteTool
{
    public function __construct(
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
        $validated = $request->validate([
            'milestone_id' => 'required|integer|exists:milestones,id',
        ]);

        $user = $request->user();
        $milestone = Milestone::findOrFail($validated['milestone_id']);

        $milestone = $this->milestoneService->complete(
            $user,
            $milestone
        );

        return Response::make(
            Response::text("Completed the milestone {$milestone->title}.")
        )->withStructuredContent((new MilestoneResource($milestone->fresh()))->resolve());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'milestone_id' => $schema->integer()
                ->description('The ID of the milestone to complete.')
                ->required(),
        ];
    }
}

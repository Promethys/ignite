<?php

namespace App\Mcp\Tools;

use App\Services\Goals\GoalService;
use App\Services\Mcp\DestructiveConfirmations;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('delete_goal')]
#[Description('Permanently delete one of the user\'s goals, along with all of its progress entries and milestones. This is irreversible and requires confirmation: call without a confirmation_token first to receive a preview of exactly what will be deleted plus a short-lived token, then call again with that token to carry out the deletion.')]
class DeleteGoalTool extends IgniteTool
{
    public function __construct(
        private readonly GoalService $goalService,
        private readonly DestructiveConfirmations $destructiveConfirmations
    ) {}

    protected function requiredAbility(): string
    {
        return 'delete';
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'goal_id' => 'required|integer|exists:goals,id',
            'confirmation_token' => 'nullable|string',
        ]);

        $token = $validated['confirmation_token'] ?? null;
        $goalId = $validated['goal_id'];

        $user = $request->user();
        $goal = $this->goalService->find($user, $goalId);
        $milestoneCount = $goal->milestones()->count();
        $entriesCount = $goal->entries()->count();
        $previewText = "This will permanently delete the goal '{$goal->title}', its {$milestoneCount} milestones and {$entriesCount} progress entries. This cannot be undone.";

        if ($token === null) {
            $token = $this->destructiveConfirmations->issue(
                $user,
                $this->name(),
                [$goal->id]
            );

            return Response::make(
                Response::text("{$previewText} This action needs a confirmation. Use the given token to confirm.")
            )->withStructuredContent([
                'requires_confirmation' => true,
                'confirmation_token' => $token,
                'preview' => $previewText,
            ]);
        }

        $consumed = $this->destructiveConfirmations->consume($user, $token, $this->name(), [$goal->id]);

        if (! $consumed) {
            return Response::error("{$previewText} The token you provided is not valid. Please ask for a valid token.");
        }

        $this->goalService->delete($user, $goal);

        return Response::text("Deleted the goal {$goal->title}.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'goal_id' => $schema
                ->integer()
                ->description('The ID of the goal to delete.')
                ->required(),
            'confirmation_token' => $schema
                ->string()
                ->description('Leave this out on the first call to receive a preview of exactly what will be deleted, together with a short-lived confirmation token. Pass that token back on a second call to carry out the deletion. A token works only once, expires after two minutes, and is only valid for this goal.')
                ->nullable(),
        ];
    }
}

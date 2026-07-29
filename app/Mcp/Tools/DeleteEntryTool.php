<?php

namespace App\Mcp\Tools;

use App\Models\GoalEntry;
use App\Services\Goals\GoalEntryService;
use App\Services\Mcp\DestructiveConfirmations;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('delete_entry')]
#[Description('Permanently delete a single progress entry from one of the user\'s goals. For a non-recurring goal the goal\'s current value is adjusted back by the entry\'s increment. This is irreversible and requires confirmation: call without a confirmation_token first to receive a preview and a short-lived token, then call again with that token.')]
class DeleteEntryTool extends IgniteTool
{
    public function __construct(
        private readonly GoalEntryService $goalEntryService,
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
            'entry_id' => 'required|integer|exists:goal_entries,id',
            'confirmation_token' => 'nullable|string',
        ]);

        $user = $this->actor($request);
        $token = $validated['confirmation_token'] ?? null;
        $entry = GoalEntry::findOrFail($validated['entry_id']);

        Gate::forUser($user)->authorize('delete', $entry);

        $goal = $entry->goal;
        $entryDate = $entry->entry_date->toDateString();

        $previewText = $goal->type === 'recurring'
            ? sprintf(
                "This will permanently delete the check-in recorded on %s for the goal '%s'. The goal's current value is not affected. This cannot be undone.",
                $entryDate,
                $goal->title,
            )
            : sprintf(
                "This will permanently delete the progress entry of %+g recorded on %s for the goal '%s', moving its current value from %g to %g. This cannot be undone.",
                $entry->increment_value,
                $entryDate,
                $goal->title,
                $goal->current_value,
                $goal->current_value - $entry->increment_value,
            );

        if ($token === null) {
            $token = $this->destructiveConfirmations->issue(
                $user,
                $this->name(),
                [$entry->id]
            );

            return Response::make(
                Response::text("{$previewText} This action needs a confirmation. Use the given token to confirm.")
            )->withStructuredContent([
                'requires_confirmation' => true,
                'confirmation_token' => $token,
                'preview' => $previewText,
            ]);
        }

        $consumed = $this->destructiveConfirmations->consume($user, $token, $this->name(), [$entry->id]);

        if (! $consumed) {
            return Response::error("{$previewText} The token you provided is not valid. Please ask for a valid token.");
        }

        $this->goalEntryService->deleteEntry(
            $user,
            $entry,
        );

        return Response::text("Deleted the progress entry from {$entry->entry_date->toDateString()} on '{$entry->goal->title}'");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'entry_id' => $schema->integer()
                ->description('The ID of the goal entry to delete.')
                ->required(),
            'confirmation_token' => $schema->string()
                ->description('Leave this out on the first call to receive a preview of what will be deleted, together with a short-lived confirmation token. Pass that token back on a second call to carry out the deletion. A token works only once, expires after two minutes, and is only valid for this entry.')
                ->nullable(),
        ];
    }
}

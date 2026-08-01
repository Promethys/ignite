<?php

namespace App\Mcp\Tools;

use App\Http\Resources\GoalEntryResource;
use App\Models\GoalEntry;
use App\Rules\GoalEntryRules;
use App\Services\Goals\GoalEntryService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('update_entry')]
#[Description('Edit an existing progress entry on one of the user\'s goals, supplying a new increment. The goal\'s current value is adjusted by the difference between the new and previous increment so history stays consistent.')]
class UpdateEntryTool extends IgniteTool
{
    public function __construct(
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
        $validated = $this->validateTrimmed($request, [
            'entry_id' => 'required|integer|exists:goal_entries,id',
            ...GoalEntryRules::progressRules(),
        ]);

        $user = $this->actor($request);
        $goalEntry = GoalEntry::findOrFail($validated['entry_id']);

        $entry = $this->goalEntryService->updateEntry(
            $user,
            $goalEntry,
            $validated['increment'],
            $validated['note'] ?? null
        );

        return Response::make(
            Response::text('Updated the progress entry.')
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
            'entry_id' => $schema->integer()
                ->description('The ID of the progress entry to edit.')
                ->required(),
            'increment' => $schema->number()
                ->description('The new increment value for this entry, replacing its previous one. The goal\'s current value shifts by the difference.')
                ->required(),
            'note' => $schema->string()
                ->description('An optional note to store on the entry, up to 2000 characters.')
                ->max(2000)
                ->nullable(),
        ];
    }
}

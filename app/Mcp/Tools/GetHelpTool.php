<?php

namespace App\Mcp\Tools;

use App\Services\Help\UserGuide;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Name('get_help')]
#[Description('Return a page of the Ignite user guide. Read the relevant page before explaining how Ignite works (goal types, logging progress, streaks, milestones, the built-in assistant) instead of answering from memory.')]
class GetHelpTool extends IgniteTool
{
    public function __construct(private readonly UserGuide $guide) {}

    protected function requiredAbility(): string
    {
        return 'read';
    }

    public function shouldRegister(Request $request): bool
    {
        return parent::shouldRegister($request) && $this->guide->topics() !== [];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $topic = $this->validateTrimmed($request, [
            'topic' => ['required', 'string', Rule::in(array_keys($this->guide->topics()))],
        ])['topic'];

        $title = $this->guide->topics()[$topic];

        return $this->structuredResponse("The \"{$title}\" page of the user guide.", [
            'topic' => $topic,
            'title' => $title,
            'content' => $this->guide->page($topic),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $pages = collect($this->guide->topics())
            ->map(fn (string $title, string $topic): string => "{$topic} ({$title})")
            ->implode(', ');

        return [
            'topic' => $schema->string()
                ->enum(array_keys($this->guide->topics()))
                ->description("The page to read: {$pages}.")
                ->required(),
        ];
    }
}

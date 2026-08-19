<?php

namespace App\Mcp\Tools;

use App\Http\Resources\CategoryResource;
use App\Rules\CategoryRules;
use App\Services\Categories\CategoryService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('create_category')]
#[Description('Create a category for the authenticated user. Categories group goals, and a goal belongs to at most one of them. Only `name` is required; everything else has a server-side default.')]
class CreateCategoryTool extends IgniteTool
{
    public function __construct(
        private readonly CategoryService $categoryService
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
        $validated = $this->validateTrimmed($request, CategoryRules::rules());

        $category = $this->categoryService->create($this->actor($request), $validated);

        return Response::make(
            Response::text("Created the category {$category->name}.")
        )->withStructuredContent([
            'category' => (new CategoryResource($category))->resolve(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The category name.')
                ->max(100)
                ->required(),
            'description' => $schema->string()
                ->description('An optional note about what the category is for.')
                ->nullable(),
            'color' => $schema->string()
                ->description('A six-digit hex colour with a leading hash, for example "#6366f1". Defaults to "#6366f1".')
                ->nullable(),
            'icon' => $schema->string()
                ->description('An optional icon for the category.')
                ->max(50)
                ->nullable(),
            'order' => $schema->integer()
                ->description('Display position among the user\'s categories. Defaults to the end of the list.')
                ->min(0)
                ->nullable(),
        ];
    }
}

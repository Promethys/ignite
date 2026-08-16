<?php

namespace App\Mcp\Tools;

use App\Http\Resources\CategoryResource;
use App\Services\Categories\CategoryService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Name('list_categories')]
#[Description('List the authenticated user\'s categories in display order, each with the number of goals filed under it. Call this to discover the category ids the goal tools accept, rather than guessing one.')]
class ListCategoriesTool extends IgniteTool
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    protected function requiredAbility(): string
    {
        return 'read';
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $categories = $this->categoryService->listForUser($this->actor($request));

        return Response::make(
            Response::text('Retrieved '.$categories->count().' categories.')
        )->withStructuredContent([
            'categories' => CategoryResource::collection($categories)->resolve(),
            'total' => $categories->count(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

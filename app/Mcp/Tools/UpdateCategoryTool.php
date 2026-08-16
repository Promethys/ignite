<?php

namespace App\Mcp\Tools;

use App\Http\Resources\CategoryResource;
use App\Rules\CategoryRules;
use App\Services\Categories\CategoryService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('update_category')]
#[Description('Update one of the authenticated user\'s categories. Only the fields you want to change need to be supplied; omitted fields keep their current value. Always provide `category_id`; every other parameter is optional.')]
class UpdateCategoryTool extends IgniteTool
{
    public function __construct(
        private readonly CategoryService $categoryService
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
        $categoryId = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
        ])['category_id'];

        $user = $this->actor($request);
        $category = $this->categoryService->find($user, $categoryId);

        $provided = $this->normalizedArguments($request);
        unset($provided['category_id']);

        if ($provided === []) {
            return Response::error('No fields were provided to update.');
        }

        $rules = CategoryRules::partialRules();

        $merged = array_merge($category->only(array_keys($rules)), $provided);

        $validated = Validator::validate($merged, $rules);

        $updateAttributes = array_intersect_key($validated, $provided);

        $updated = $this->categoryService->update($user, $category, $updateAttributes);

        return Response::make(
            Response::text("Updated the category {$updated->name}.")
        )->withStructuredContent([
            'category' => (new CategoryResource($updated))->resolve(),
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
            'category_id' => $schema->integer()
                ->description('The ID of the category to update.')
                ->required(),
            'name' => $schema->string()
                ->description('The category name.')
                ->max(100)
                ->nullable(),
            'description' => $schema->string()
                ->description('A note about what the category is for.')
                ->nullable(),
            'color' => $schema->string()
                ->description('A six-digit hex colour with a leading hash, for example "#6366f1".')
                ->nullable(),
            'icon' => $schema->string()
                ->description('An icon for the category.')
                ->max(50)
                ->nullable(),
            'order' => $schema->integer()
                ->description('Display position among the user\'s categories.')
                ->min(0)
                ->nullable(),
        ];
    }
}

<?php

namespace App\Mcp\Tools;

use App\Services\Categories\CategoryService;
use App\Services\Mcp\DestructiveConfirmations;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('delete_category')]
#[Description('Permanently delete one of the user\'s categories. The goals filed under it are kept and become uncategorised. This is irreversible and requires confirmation: call without a confirmation_token first to receive a preview and a short-lived token, then call again with that token to carry out the deletion.')]
class DeleteCategoryTool extends IgniteTool
{
    public function __construct(
        private readonly CategoryService $categoryService,
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
            'category_id' => 'required|integer|exists:categories,id',
            'confirmation_token' => 'nullable|string',
        ]);

        $token = $validated['confirmation_token'] ?? null;

        $user = $this->actor($request);
        $category = $this->categoryService->find($user, $validated['category_id']);
        $goalsCount = $category->goals_count;
        $previewText = "This will permanently delete the category '{$category->name}'. Goals filed under it are kept and become uncategorised ({$goalsCount} affected). This cannot be undone.";

        if ($token === null) {
            $token = $this->destructiveConfirmations->issue(
                $user,
                $this->name(),
                [$category->id]
            );

            return Response::make(
                Response::text("{$previewText} This action needs a confirmation. Use the given token to confirm.")
            )->withStructuredContent([
                'requires_confirmation' => true,
                'confirmation_token' => $token,
                'preview' => $previewText,
            ]);
        }

        $consumed = $this->destructiveConfirmations->consume($user, $token, $this->name(), [$category->id]);

        if (! $consumed) {
            return Response::error("{$previewText} The token you provided is not valid. Please ask for a valid token.");
        }

        $this->categoryService->delete($user, $category);

        return Response::text("Deleted the category {$category->name}.");
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
                ->description('The ID of the category to delete.')
                ->required(),
            'confirmation_token' => $schema->string()
                ->description('Leave this out on the first call to receive a preview of what will be deleted, together with a short-lived confirmation token. Pass that token back on a second call to carry out the deletion. A token works only once, expires after two minutes, and is only valid for this category.')
                ->nullable(),
        ];
    }
}

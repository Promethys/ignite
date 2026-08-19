<?php

namespace App\Services\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Category read and write operations. The acting user is always passed
 * explicitly; this service never calls `auth()`. Authorization is enforced
 * via `Gate::forUser($actor)`, keeping the policy as the single authority.
 */
class CategoryService
{
    /**
     * The goal counts every category is returned with, so a category has the
     * same shape whichever method produced it.
     *
     * @return array<int|string, mixed>
     */
    private function goalCounts(): array
    {
        return [
            'goals',
            'goals as active_goals_count' => fn ($query) => $query->where('status', 'in_progress'),
            'goals as completed_goals_count' => fn ($query) => $query->where('status', 'completed'),
        ];
    }

    /**
     * Return the actor's categories in display order, each with the number
     * of goals filed under it and how many of those are active or completed.
     *
     * @return Collection<int, Category>
     */
    public function listForUser(User $actor): Collection
    {
        Gate::forUser($actor)->authorize('viewAny', Category::class);

        return $actor->categories()
            ->withCount($this->goalCounts())
            ->orderBy('order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Load a single category for the actor, with its goal count.
     *
     * Authorizes `view` via `Gate::forUser($actor)`. A missing id throws a
     * `ModelNotFoundException` (404); a foreign id the actor may not view
     * throws an `AuthorizationException` (403).
     *
     * Accepts either a primary key (used by MCP tools, which receive a
     * `category_id`) or an already-resolved model.
     */
    public function find(User $actor, Category|int $category): Category
    {
        $category = $category instanceof Category ? $category : Category::findOrFail($category);

        Gate::forUser($actor)->authorize('view', $category);

        return $category->loadCount($this->goalCounts());
    }

    /**
     * Create a category owned by the actor. A missing `order` places it at
     * the end of the actor's list.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $actor, array $attributes): Category
    {
        Gate::forUser($actor)->authorize('create', Category::class);

        $category = Category::create([
            ...$attributes,
            'user_id' => $actor->id,
        ]);

        return $category->loadCount($this->goalCounts());
    }

    /**
     * Update a category with the given (already-validated) attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $actor, Category $category, array $attributes): Category
    {
        Gate::forUser($actor)->authorize('update', $category);

        $category->update($attributes);

        return $category->loadCount($this->goalCounts());
    }

    /**
     * Delete a category. Its goals are kept and become uncategorised, which
     * the `goals.category_id` foreign key does with `on delete set null`.
     */
    public function delete(User $actor, Category $category): void
    {
        Gate::forUser($actor)->authorize('delete', $category);

        $category->delete();
    }
}

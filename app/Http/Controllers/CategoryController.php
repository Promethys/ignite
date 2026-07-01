<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CategoryController extends Controller
{
    protected $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'color' => 'nullable|string|max:10',
        'icon' => 'nullable|string|max:50',
        'order' => 'nullable|integer',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $hasValidQueryParam = $request->boolean('create');

        return Inertia::render('Categories/Index', [
            'items' => auth()->user()->categories()->withCount([
                'goals',
                'goals as active_goals_count' => fn ($query) => $query->where('status', 'in_progress'),
                'goals as completed_goals_count' => fn ($query) => $query->where('status', 'completed'),
            ])->get(),
            'openCreate' => $hasValidQueryParam,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user()->load('categories');

        return Inertia::render('Categories/Create', [
            'user' => [
                'id' => $user->id,
                'categories' => $user->categories->pluck('name', 'id'),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Category::class);

        $validated = $request->validate($this->rules);

        $validated['user_id'] = auth()->id();

        Category::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category created.']);

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        Gate::authorize('view', $category);

        return Inertia::render('Categories/Show', [
            'category' => $category,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        Gate::authorize('view', $category);

        $user = auth()->user()->load('categories');

        return Inertia::render('Categories/Edit', [
            'category' => $category,
            'user' => [
                'id' => $user->id,
                'categories' => $user->categories->pluck('name', 'id'),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        Gate::authorize('update', $category);

        $validated = $request->validate($this->rules);

        $category->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category updated.']);

        return to_route('categories.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        Gate::authorize('delete', $category);

        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category deleted.']);

        return redirect()->back();
    }
}

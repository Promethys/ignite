<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Categories\CategoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $hasValidQueryParam = $request->boolean('create');

        return Inertia::render('Categories/Index', [
            'items' => $this->categoryService->listForUser($request->user()),
            'openCreate' => $hasValidQueryParam,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = $request->user()->load('categories');

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
    public function store(StoreCategoryRequest $request)
    {
        $this->categoryService->create($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.category.created')]);

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Category $category)
    {
        return Inertia::render('Categories/Show', [
            'category' => $this->categoryService->find($request->user(), $category),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Category $category)
    {
        $category = $this->categoryService->find($request->user(), $category);

        $user = $request->user()->load('categories');

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
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->categoryService->update($request->user(), $category, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.category.updated')]);

        return to_route('categories.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Category $category)
    {
        $this->categoryService->delete($request->user(), $category);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.category.deleted')]);

        return redirect()->back();
    }
}

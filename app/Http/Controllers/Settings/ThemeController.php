<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreThemeRequest;
use App\Http\Requests\Settings\UpdateThemeRequest;
use App\Models\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ThemeController extends Controller
{
    /**
     * Save a new theme and start using it.
     */
    public function store(StoreThemeRequest $request): RedirectResponse
    {
        $theme = $request->user()->themes()->create($request->validated());
        $theme->activate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.theme.created', ['name' => $theme->name])]);

        return back();
    }

    /**
     * Change the name or the colours of a theme.
     */
    public function update(UpdateThemeRequest $request, int $theme): RedirectResponse
    {
        $theme = $this->ownedTheme($request, $theme);
        $theme->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.theme.updated', ['name' => $theme->name])]);

        return back();
    }

    /**
     * Use this theme across the app.
     */
    public function activate(Request $request, int $theme): RedirectResponse
    {
        $theme = $this->ownedTheme($request, $theme);
        $theme->activate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.theme.activated', ['name' => $theme->name])]);

        return back(303);
    }

    /**
     * Go back to the built-in theme.
     */
    public function useDefault(Request $request): RedirectResponse
    {
        $request->user()->themes()->where('is_active', true)->update(['is_active' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.theme.default')]);

        return back(303);
    }

    /**
     * Delete one of the user's themes.
     */
    public function destroy(Request $request, int $theme): RedirectResponse
    {
        $theme = $this->ownedTheme($request, $theme);
        $theme->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.theme.removed', ['name' => $theme->name])]);

        return back(303);
    }

    private function ownedTheme(Request $request, int $themeId): Theme
    {
        return $request->user()->themes()->findOrFail($themeId);
    }
}

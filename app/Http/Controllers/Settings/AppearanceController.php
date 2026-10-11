<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    /**
     * Show the appearance settings and the user's themes.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Appearance', [
            'themes' => $request->user()
                ->themes()
                ->oldest('id')
                ->get(['id', 'user_id', 'name', 'light_color', 'dark_color', 'is_active'])
                ->map
                ->only(['id', 'name', 'light_color', 'dark_color', 'is_active']),
            'themeLimit' => Theme::MAX_PER_USER,
        ]);
    }
}

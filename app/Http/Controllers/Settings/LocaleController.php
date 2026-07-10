<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', array_keys(config('locales.supported')))],
        ]);

        $request->user()->update(['locale' => $validated['locale']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Language updated.']);

        return redirect()->back();
    }
}

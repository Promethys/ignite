<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreApiTokenRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    /**
     * Allowed token abilities. `write` implies `read` at issue time.
     */
    public const ALLOWED_ABILITIES = ['read', 'write', 'delete'];

    /**
     * Show the user's personal access tokens.
     */
    public function index(Request $request): Response
    {
        $tokens = $request->user()
            ->tokens()
            ->select(['id', 'name', 'abilities', 'last_used_at', 'created_at'])
            ->get();

        return Inertia::render('settings/ApiTokens', [
            'tokens' => $tokens,
            // The freshly minted plaintext token, flashed once from the store
            // action. It is never persisted and never re-served.
            'newToken' => fn () => $request->session()->get('newApiToken'),
        ]);
    }

    /**
     * Create a new personal access token.
     */
    public function store(StoreApiTokenRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $abilities = $this->normalizeAbilities($validated['abilities']);

        $plainTextToken = $request->user()
            ->createToken($validated['name'], $abilities)
            ->plainTextToken;

        // Flash the plaintext token to the session for a one-time reveal on
        // the index page. It is displayed once and never stored or returned again.
        $request->session()->flash('newApiToken', [
            'name' => $validated['name'],
            'token' => $plainTextToken,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.api_token.created')]);

        return back();
    }

    /**
     * Revoke one of the current user's tokens.
     */
    public function destroy(Request $request, string $token): RedirectResponse
    {
        $request->user()
            ->tokens()
            ->where('id', $token)
            ->firstOrFail()
            ->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.api_token.revoked')]);

        return back(303);
    }

    /**
     * Normalize the requested abilities: `write` implies `read`, and the
     * result is ordered canonically (read, write, delete).
     *
     * @param  array<int, string>  $abilities
     * @return array<int, string>
     */
    private function normalizeAbilities(array $abilities): array
    {
        $unique = array_values(array_unique($abilities));

        if (in_array('write', $unique, true)) {
            $unique[] = 'read';
        }

        $unique = array_values(array_unique($unique));

        return array_values(array_filter(
            self::ALLOWED_ABILITIES,
            fn (string $ability): bool => in_array($ability, $unique, true),
        ));
    }
}

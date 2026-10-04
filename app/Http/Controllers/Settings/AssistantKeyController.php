<?php

namespace App\Http\Controllers\Settings;

use App\Enums\AssistantProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreAssistantKeyRequest;
use App\Http\Requests\Settings\UpdateAssistantKeyRequest;
use App\Models\AssistantKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssistantKeyController extends Controller
{
    /**
     * Show the supported providers and the user's saved keys.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('settings/Assistant', [
            'providers' => collect(AssistantProvider::cases())->map(fn (AssistantProvider $provider): array => [
                'value' => $provider->value,
                'label' => $provider->label(),
            ]),
            'assistantKeys' => $request->user()
                ->assistantKeys()
                ->get(['id', 'provider', 'key_suffix', 'model', 'is_default']),
        ]);
    }

    /**
     * Save a key for a provider the user has not connected yet.
     */
    public function store(StoreAssistantKeyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $assistantKey = $request->user()->assistantKeys()->create([
            'provider' => $validated['provider'],
            'api_key' => $validated['api_key'],
            'key_suffix' => substr($validated['api_key'], -4),
            'model' => $validated['model'] ?? null,
            'consented_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.assistant_key.connected', ['provider' => $assistantKey->provider->label()])]);

        return back();
    }

    /**
     * Replace the key or change the model of a saved key.
     */
    public function update(UpdateAssistantKeyRequest $request, int $assistantKey): RedirectResponse
    {
        $assistantKey = $this->ownedKey($request, $assistantKey);
        $validated = $request->validated();

        $attributes = ['model' => $validated['model'] ?? null];

        if (filled($validated['api_key'] ?? null)) {
            $attributes['api_key'] = $validated['api_key'];
            $attributes['key_suffix'] = substr($validated['api_key'], -4);
        }

        $assistantKey->update($attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.assistant_key.updated', ['provider' => $assistantKey->provider->label()])]);

        return back();
    }

    /**
     * Use this key for the assistant.
     */
    public function makeDefault(Request $request, int $assistantKey): RedirectResponse
    {
        $assistantKey = $this->ownedKey($request, $assistantKey);
        $assistantKey->markAsDefault();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.assistant_key.default', ['provider' => $assistantKey->provider->label()])]);

        return back(303);
    }

    /**
     * Remove one of the user's saved keys.
     */
    public function destroy(Request $request, int $assistantKey): RedirectResponse
    {
        $assistantKey = $this->ownedKey($request, $assistantKey);
        $assistantKey->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.assistant_key.removed', ['provider' => $assistantKey->provider->label()])]);

        return back(303);
    }

    private function ownedKey(Request $request, int $assistantKeyId): AssistantKey
    {
        return $request->user()->assistantKeys()->findOrFail($assistantKeyId);
    }
}

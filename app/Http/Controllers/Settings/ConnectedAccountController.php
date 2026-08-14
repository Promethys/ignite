<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConnectedAccountController extends Controller
{
    public function index(Request $request)
    {
        $configuredProviders = collect(config('auth.sso.supported', []))
            ->filter(fn ($provider) => config("services.{$provider}.client_id"))
            ->values()
            ->all();

        if (empty($configuredProviders)) {
            abort(404);
        }

        $connectedProviders = $request->user()
            ->socialAccounts()
            ->select('id', 'provider', 'provider_data->email as provider_email', 'created_at')
            ->get();

        return Inertia::render('settings/ConnectedAccounts', compact('connectedProviders'));
    }
}

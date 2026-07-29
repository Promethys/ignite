<?php

namespace App\Mcp\Tools;

use App\Http\Resources\UserResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('set_user')]
#[Description('Update the authenticated user\'s own profile. Only the fields you supply are changed; omitted fields keep their value. The mutable set is limited to `name`, `timezone`, and `locale` - email and credentials are not editable here.')]
class SetUserTool extends IgniteTool
{
    protected function requiredAbility(): string
    {
        return 'write';
    }

    /**
     * Handle the tool request.
     *
     * Partial update of the acting user's profile.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $this->validateTrimmed($request, [
            'name' => 'nullable|string|max:255',
            'timezone' => 'nullable|timezone',
            'locale' => ['nullable', 'string', Rule::in(array_keys(config('locales.supported')))],
        ]);

        $provided = array_filter($validated, fn ($value) => $value !== null);

        if ($provided === []) {
            return Response::error('No fields were provided to update.');
        }

        $user = $this->actor($request);

        $user->update($provided);

        return Response::make(
            Response::text('Updated the user\'s profile.')
        )->withStructuredContent(['user' => (new UserResource($user->fresh()))->resolve()]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The user\'s display name.')
                ->max(255)
                ->nullable(),
            'timezone' => $schema->string()
                ->description('The user\'s IANA timezone, e.g. "Europe/Paris". Determines how check-in dates are validated.')
                ->nullable(),
            'locale' => $schema->string()
                ->description('The user\'s preferred language code. One of: en, fr.')
                ->enum(array_keys(config('locales.supported')))
                ->nullable(),
        ];
    }
}

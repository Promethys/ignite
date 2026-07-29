<?php

namespace App\Mcp\Tools;

use App\Http\Resources\UserResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Name('get_user')]
#[Description('Return the authenticated user\'s profile: name, timezone, locale. Use this to learn the user\'s timezone before recording dated check-ins, and their locale to pick a response language.')]
class GetUserTool extends IgniteTool
{
    protected function requiredAbility(): string
    {
        return 'read';
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        return Response::make(
            Response::text('Retrieved the user\'s profile.')
        )->withStructuredContent(['user' => (new UserResource($this->actor($request)))->resolve()]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            //
        ];
    }
}

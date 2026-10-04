<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Tools\McpServerTool;
use Laravel\Ai\Tools\Request;

class ConfirmedDeletion extends McpServerTool implements Approvable
{
    use InteractsWithApprovals;

    private const CONFIRMATION_TOKEN = 'confirmation_token';

    public function description(): string
    {
        return Str::before(parent::description(), '. ').'. The user is asked to approve in the app before anything is deleted.';
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return Arr::except(parent::schema($schema), self::CONFIRMATION_TOKEN);
    }

    /**
     * Delete the target, confirming with a token the model never sees.
     */
    public function handle(Request $request): string
    {
        $arguments = $this->targetOf($request);
        $preview = $this->preview($arguments);

        return parent::handle(new Request(
            [...$arguments, self::CONFIRMATION_TOKEN => $preview[self::CONFIRMATION_TOKEN]],
            $request->toolCallId(),
            $request->toolInvocationId(),
        ));
    }

    protected function needsApproval(Request $request): Approval|bool
    {
        try {
            return Approval::required($this->preview($this->targetOf($request))['preview']);
        } catch (ValidationException) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{confirmation_token: string, preview: string}
     */
    private function preview(array $arguments): array
    {
        return json_decode(parent::handle(new Request($arguments)), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function targetOf(Request $request): array
    {
        return Arr::except($request->toArray(), self::CONFIRMATION_TOKEN);
    }
}

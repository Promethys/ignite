<?php

namespace App\Mcp\Resources;

use App\Services\Help\UserGuide;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class GuideResource extends Resource
{
    protected string $mimeType = 'text/markdown';

    public function __construct(private readonly string $topic, private readonly string $pageTitle) {}

    public function name(): string
    {
        return "guide-{$this->topic}";
    }

    public function title(): string
    {
        return "User guide: {$this->pageTitle}";
    }

    public function description(): string
    {
        return "The \"{$this->pageTitle}\" page of the Ignite user guide.";
    }

    public function uri(): string
    {
        return "ignite://guide/{$this->topic}";
    }

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        return Response::text((string) app(UserGuide::class)->page($this->topic));
    }
}

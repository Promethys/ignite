<?php

namespace App\Services\Help;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UserGuide
{
    private const HOME_PAGE = 'index';

    private const HOME_TOPIC = 'getting-started';

    /** @var array<string, string>|null */
    private ?array $titles = null;

    /**
     * The guide's pages, as topic => title.
     *
     * @return array<string, string>
     */
    public function topics(): array
    {
        return $this->titles ??= collect(File::glob($this->directory().'/*.md'))
            ->mapWithKeys(fn (string $path): array => [
                $this->topicOf($path) => $this->titleOf($path),
            ])
            ->sortKeys()
            ->all();
    }

    /**
     * The Markdown of one page, or null for a topic the guide does not have.
     */
    public function page(string $topic): ?string
    {
        if (! array_key_exists($topic, $this->topics())) {
            return null;
        }

        $page = $topic === self::HOME_TOPIC ? self::HOME_PAGE : $topic;

        return File::get("{$this->directory()}/{$page}.md");
    }

    private function directory(): string
    {
        return base_path('docs/guide');
    }

    private function topicOf(string $path): string
    {
        $page = basename($path, '.md');

        return $page === self::HOME_PAGE ? self::HOME_TOPIC : $page;
    }

    private function titleOf(string $path): string
    {
        $heading = Str::of(File::get($path))->match('/^#\s+(.+)$/m')->trim()->toString();

        return $heading !== '' ? $heading : Str::headline($this->topicOf($path));
    }
}

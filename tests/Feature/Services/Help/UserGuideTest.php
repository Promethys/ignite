<?php

namespace Tests\Feature\Services\Help;

use App\Services\Help\UserGuide;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class UserGuideTest extends TestCase
{
    private function guide(): UserGuide
    {
        return new UserGuide;
    }

    public function test_it_lists_every_page_of_the_guide_by_topic_and_title()
    {
        $topics = $this->guide()->topics();

        $this->assertSame('Getting Started', $topics['getting-started']);
        $this->assertSame('Goal Types', $topics['goal-types']);
        $this->assertSame('Tracking Progress', $topics['tracking-progress']);
        $this->assertSame('Use the Assistant', $topics['assistant']);
        $this->assertCount(count(File::glob(base_path('docs/guide/*.md'))), $topics);
        $this->assertArrayNotHasKey('index', $topics);
    }

    public function test_it_returns_a_page_as_markdown()
    {
        $this->assertStringStartsWith('# Goal Types', $this->guide()->page('goal-types'));
        $this->assertStringStartsWith('# Getting Started', $this->guide()->page('getting-started'));
    }

    public function test_it_only_reads_pages_of_the_guide()
    {
        $this->assertNull($this->guide()->page('unknown'));
        $this->assertNull($this->guide()->page('../features/encryption'));
        $this->assertNull($this->guide()->page('../../.env'));
        $this->assertNull($this->guide()->page('index'));
    }

    public function test_an_installation_without_the_guide_has_no_topics()
    {
        File::partialMock()->shouldReceive('glob')->andReturn([]);

        $this->assertSame([], $this->guide()->topics());
        $this->assertNull($this->guide()->page('goal-types'));
    }
}

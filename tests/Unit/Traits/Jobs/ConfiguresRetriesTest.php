<?php

namespace Tests\Unit\Traits\Jobs;

use App\Traits\Jobs\ConfiguresRetries;
use Tests\TestCase;

class ConfiguresRetriesTest extends TestCase
{
    private function job(): object
    {
        return new class
        {
            use ConfiguresRetries;
        };
    }

    public function test_uses_local_retry_settings_outside_production()
    {
        // The test environment is not production.
        $job = $this->job();

        $this->assertSame(2, $job->tries());
        $this->assertSame([5], $job->backoff());
    }

    public function test_uses_resilient_retry_settings_in_production()
    {
        $this->app->detectEnvironment(fn () => 'production');

        $job = $this->job();

        $this->assertSame(5, $job->tries());
        $this->assertSame([30, 60, 120], $job->backoff());
    }
}

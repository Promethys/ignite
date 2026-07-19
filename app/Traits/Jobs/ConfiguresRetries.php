<?php

namespace App\Traits\Jobs;

trait ConfiguresRetries
{
    /**
     * Determine number of times the job may be attempted.
     */
    public function tries(): int
    {
        return app()->environment('production')
            ? 5
            : 2;
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): int|array
    {
        return app()->environment('production')
            ? [30, 60, 120]
            : [5];
    }
}

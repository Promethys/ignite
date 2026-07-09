<?php

namespace App\DTOs;

use Carbon\CarbonInterface;

readonly class StreakData
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public int $current,
        public int $longest,
        public string $unit,
        public bool $currentPeriodSatisfied,
        public ?CarbonInterface $anchorDate
    ) {}
}

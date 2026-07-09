<?php

namespace App\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;

readonly class StreakData implements Arrayable
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

    public function toArray(): array
    {
        return [
            'current' => $this->current,
            'longest' => $this->longest,
            'unit' => $this->unit,
            'current_period_satisfied' => $this->currentPeriodSatisfied,
        ];
    }
}

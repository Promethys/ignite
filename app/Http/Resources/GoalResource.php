<?php

namespace App\Http\Resources;

use App\DTOs\StreakData;
use App\Models\Category;
use App\Models\GoalEntry;
use App\Models\Milestone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $type
 * @property string|null $current_value
 * @property string|null $target_value
 * @property string|null $initial_value
 * @property string|null $unit
 * @property string $direction
 * @property string|null $polarity
 * @property string $status
 * @property string $priority
 * @property string|null $recurrence
 * @property Carbon|null $start_date
 * @property Carbon|null $deadline
 * @property Carbon|null $completed_at
 * @property float|int|null $progress_percentage
 * @property bool $is_overdue
 * @property bool $is_completed
 * @property StreakData|null $streak
 * @property Category|null $category
 * @property Collection<int, GoalEntry> $entries
 * @property Collection<int, Milestone> $milestones
 */
class GoalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,

            'current_value' => $this->current_value,
            'target_value' => $this->target_value,
            'initial_value' => $this->initial_value,
            'unit' => $this->unit,
            'direction' => $this->direction,
            'polarity' => $this->polarity,

            'status' => $this->status,
            'priority' => $this->priority,
            'recurrence' => $this->recurrence,
            'start_date' => $this->start_date,
            'deadline' => $this->deadline,
            'completed_at' => $this->completed_at,

            'progress_percentage' => $this->progress_percentage,
            'is_overdue' => $this->is_overdue,
            'is_completed' => $this->is_completed,
            'streak' => $this->streak?->toArray(),

            'milestone_summary' => $this->milestoneSummary(),

            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'entries' => GoalEntryResource::collection($this->whenLoaded('entries')),
            'milestones' => MilestoneResource::collection($this->whenLoaded('milestones')),
        ];
    }

    /**
     * Completed / total milestone counts.
     *
     * @return array{total: int, completed: int}
     */
    protected function milestoneSummary(): array
    {
        if ($this->resource->relationLoaded('milestones')) {
            $milestones = $this->resource->milestones;

            return [
                'total' => $milestones->count(),
                'completed' => $milestones->filter(fn (Milestone $milestone) => ! empty($milestone->completed_at))->count(),
            ];
        }

        return [
            'total' => (int) ($this->resource->getAttribute('milestones_count') ?? 0),
            'completed' => (int) ($this->resource->getAttribute('completed_milestones_count') ?? 0),
        ];
    }
}

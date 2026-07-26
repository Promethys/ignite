<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $goal_id
 * @property string|null $value
 * @property string|null $previous_value
 * @property float $increment_value
 * @property string|null $note
 * @property Carbon|null $entry_date
 */
class GoalEntryResource extends JsonResource
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
            'goal_id' => $this->goal_id,
            'value' => $this->value,
            'previous_value' => $this->previous_value,
            'increment_value' => $this->increment_value,
            'note' => $this->note,
            'entry_date' => $this->entry_date,
        ];
    }
}

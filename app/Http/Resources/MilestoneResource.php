<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $title
 * @property int $order
 * @property string|null $target_value
 * @property bool $is_reached
 * @property bool $is_completed
 */
class MilestoneResource extends JsonResource
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
            'order' => $this->order,
            'target_value' => $this->target_value,
            'is_reached' => $this->is_reached,
            'is_completed' => $this->is_completed,
        ];
    }
}

<?php

namespace App\Rules;

use App\Traits\Rules\HandlesPartialRules;

/**
 * Milestone validation rules shared by the web controller and the MCP tools.
 */
class MilestoneRules
{
    use HandlesPartialRules;

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'target_value' => 'nullable|numeric',
            'description' => 'nullable|string',
            // 'deadline' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'points_reward' => 'nullable|numeric',
            'order' => 'nullable|integer',
        ];
    }
}

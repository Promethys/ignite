<?php

namespace App\Http\Requests\Goals;

use App\Models\Goal;
use App\Rules\GoalRules;
use App\Rules\MilestoneRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Goal::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = GoalRules::rules($this->user());

        if ($this->input('type') === 'quantifiable') {
            $rules['target_value'] = 'required|numeric';
        }

        return [
            ...$rules,
            'steps' => 'exclude_unless:type,multi_step|nullable|array|max:50',
            'steps.*.title' => 'exclude_unless:type,multi_step|required|string|max:255',
            'steps.*.deadline' => [
                'exclude_unless:type,multi_step',
                ...MilestoneRules::deadlineRules(
                    $this->filled('start_date') ? 'start_date' : null,
                    $this->filled('deadline') ? 'deadline' : null,
                ),
            ],
        ];
    }
}

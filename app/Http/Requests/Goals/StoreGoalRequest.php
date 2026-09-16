<?php

namespace App\Http\Requests\Goals;

use App\Models\Goal;
use App\Rules\GoalRules;
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

        return $rules;
    }
}

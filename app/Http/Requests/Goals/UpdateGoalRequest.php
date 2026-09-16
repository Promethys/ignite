<?php

namespace App\Http\Requests\Goals;

use App\Rules\GoalRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('goal'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return GoalRules::rules($this->user());
    }
}

<?php

namespace App\Http\Requests\Goals;

use App\Models\Goal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReorderMilestonesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->goal());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'milestones' => ['required', 'array', 'size:'.$this->goal()->milestones()->count()],
            'milestones.*' => [
                'integer',
                'distinct',
                Rule::exists('milestones', 'id')->where('goal_id', $this->goal()->id),
            ],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->goal()->type !== 'multi_step') {
                    $validator->errors()->add('milestones', __('validation.custom.milestones.reorder_multi_step_only'));
                }
            },
        ];
    }

    private function goal(): Goal
    {
        return $this->route('goal');
    }
}

<?php

namespace App\Http\Requests\Goals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderPinnedGoalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'goals' => ['required', 'array', 'size:'.$this->user()->goals()->whereNotNull('pin_order')->count()],
            'goals.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('goals', 'id')->where('user_id', $this->user()->id)->whereNotNull('pin_order'),
            ],
        ];
    }
}

<?php

namespace App\Http\Requests\Goals;

use App\Rules\MilestoneRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('milestone'));
    }

    /**
     * The goal in the URL must be the one that owns the milestone, settled
     * before validation so a mismatch is never reported as a rule failure.
     */
    protected function prepareForValidation(): void
    {
        if ($this->route('goal')->isNot($this->route('milestone')->goal)) {
            abort(403);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return MilestoneRules::rules();
    }
}

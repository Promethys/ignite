<?php

namespace App\Http\Requests\Goals;

use App\Models\Milestone;
use App\Rules\MilestoneRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Milestone::class, $this->route('goal')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return MilestoneRules::rules();
    }
}

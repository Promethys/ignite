<?php

namespace App\Http\Requests;

use App\Rules\CategoryRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('category'));
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return CategoryRules::rules();
    }
}

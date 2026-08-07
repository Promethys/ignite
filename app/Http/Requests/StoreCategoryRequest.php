<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Rules\CategoryRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Category::class);
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return CategoryRules::rules();
    }
}

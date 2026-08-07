<?php

namespace App\Rules;

/**
 * Category validation rules shared by the store and update form requests.
 */
class CategoryRules
{
    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:10',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
        ];
    }
}

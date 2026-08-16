<?php

namespace App\Rules;

use App\Traits\Rules\HandlesPartialRules;

/**
 * Category validation rules shared by the form requests and the MCP tools.
 */
class CategoryRules
{
    use HandlesPartialRules;

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
        ];
    }
}

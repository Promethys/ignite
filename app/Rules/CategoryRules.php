<?php

namespace App\Rules;

use App\Models\User;
use App\Traits\Rules\HandlesPartialRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

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

    public static function ownerIdRule(User $user): Exists
    {
        return Rule::exists('categories', 'id')->where('user_id', $user->id);
    }
}

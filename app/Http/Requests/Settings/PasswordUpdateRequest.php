<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PasswordUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $hasPassword = $this->user()->has_password;
        $rules = [];

        if ($hasPassword) {
            $rules = [
                'current_password' => ['required', 'current_password'],
            ];
        }

        return [
            ...$rules,
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];
    }
}

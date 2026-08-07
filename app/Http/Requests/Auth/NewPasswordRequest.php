<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class NewPasswordRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'confirmed',
                // The compromised-password check calls an external API, so it
                // is kept for the real submission rather than every keystroke.
                $this->isPrecognitive()
                    ? Rules\Password::min(8)->mixedCase()->numbers()->symbols()
                    : Rules\Password::defaults(),
            ],
        ];
    }
}

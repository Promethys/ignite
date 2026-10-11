<?php

namespace App\Http\Requests\Settings;

use App\Rules\ThemeRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateThemeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ThemeRules::rules();
    }
}

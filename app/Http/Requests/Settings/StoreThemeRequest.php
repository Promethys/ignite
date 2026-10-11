<?php

namespace App\Http\Requests\Settings;

use App\Models\Theme;
use App\Rules\ThemeRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreThemeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ThemeRules::rules();
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->user()->themes()->count() >= Theme::MAX_PER_USER) {
                    $validator->errors()->add('name', __('settings.themes.limit_reached', ['max' => Theme::MAX_PER_USER]));
                }
            },
        ];
    }
}

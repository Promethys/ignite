<?php

namespace App\Http\Requests\Settings;

use App\Http\Controllers\Settings\ApiTokenController;
use Illuminate\Foundation\Http\FormRequest;

class StoreApiTokenRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['required', 'array'],
            'abilities.*' => ['in:'.implode(',', ApiTokenController::ALLOWED_ABILITIES)],
        ];
    }
}

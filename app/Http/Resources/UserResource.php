<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Whitelist resource for the acting user.
 *
 * Deliberately excludes email, password, two-factor, and role data: this
 * payload is sent to a third-party LLM, and a mutable email would be an
 * account-takeover vector under prompt injection.
 *
 * @property int $id
 * @property string $name
 * @property string|null $timezone
 * @property string|null $locale
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
        ];
    }
}

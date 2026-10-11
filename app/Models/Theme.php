<?php

namespace App\Models;

use App\Exceptions\UserDataEncryptionException;
use App\Services\Encryption\UserDataKeyring;
use App\Traits\Models\EncryptsUserData;
use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Theme extends Model
{
    use EncryptsUserData;

    /** @use HasFactory<ThemeFactory> */
    use HasFactory;

    public const MAX_PER_USER = 5;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'light_color',
        'dark_color',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Store the light mode colour in lowercase.
     */
    public function lightColor(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower($value),
        );
    }

    /**
     * Store the dark mode colour in lowercase.
     */
    public function darkColor(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : strtolower($value),
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Make this the only theme in use for its user.
     */
    public function activate(): void
    {
        DB::transaction(function (): void {
            static::query()
                ->where('user_id', $this->user_id)
                ->whereKeyNot($this->getKey())
                ->update(['is_active' => false]);

            $this->forceFill(['is_active' => true])->save();
        });
    }

    protected function encryptedAttributes(): array
    {
        return ['name'];
    }

    protected function userDataKeyId(): string
    {
        $userId = $this->user_id ?? throw UserDataEncryptionException::missingOwner('theme');

        return app(UserDataKeyring::class)->keyIdForUser($userId);
    }
}

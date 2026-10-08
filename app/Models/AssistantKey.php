<?php

namespace App\Models;

use App\Enums\AssistantProvider;
use App\Exceptions\UserDataEncryptionException;
use App\Services\Encryption\UserDataKeyring;
use App\Traits\Models\EncryptsUserData;
use Database\Factories\AssistantKeyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AssistantKey extends Model
{
    use EncryptsUserData;

    /** @use HasFactory<AssistantKeyFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'key_suffix',
        'api_key',
        'model',
        'consented_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'api_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'provider' => AssistantProvider::class,
        'is_default' => 'boolean',
        'consented_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function (AssistantKey $assistantKey): void {
            $assistantKey->is_default = ! static::query()->where('user_id', $assistantKey->user_id)->exists();
        });

        static::deleted(function (AssistantKey $assistantKey): void {
            if ($assistantKey->is_default) {
                static::query()->where('user_id', $assistantKey->user_id)->latest('id')->first()?->markAsDefault();
            }
        });
    }

    /**
     * Make this the only default key of its user.
     */
    public function markAsDefault(): void
    {
        DB::transaction(function (): void {
            static::query()
                ->where('user_id', $this->user_id)
                ->whereKeyNot($this->getKey())
                ->update(['is_default' => false]);

            $this->forceFill(['is_default' => true])->save();
        });
    }

    protected function encryptedAttributes(): array
    {
        return ['api_key'];
    }

    protected function userDataKeyId(): string
    {
        $userId = $this->user_id ?? throw UserDataEncryptionException::missingOwner('assistant_key');

        return app(UserDataKeyring::class)->keyIdForUser($userId);
    }
}

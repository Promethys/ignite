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
        'consented_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

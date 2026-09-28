<?php

namespace App\Models;

use App\Exceptions\UserDataEncryptionException;
use App\Observers\CategoryObserver;
use App\Services\Encryption\UserDataKeyring;
use App\Traits\Models\EncryptsUserData;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(CategoryObserver::class)]
class Category extends Model
{
    use EncryptsUserData;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'color',
        'icon',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the user that owns the category.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the goals for the category.
     *
     * @return HasMany<Goal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * Store the colour in lowercase.
     */
    public function color(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower($value),
        );
    }

    protected function encryptedAttributes(): array
    {
        return ['name', 'description'];
    }

    protected function userDataKeyId(): string
    {
        $userId = $this->user_id ?? throw UserDataEncryptionException::missingOwner('category');

        return app(UserDataKeyring::class)->keyIdForUser($userId);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_achievements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'achievement_id',
        'progress',
        'unlocked_at',
        'is_seen',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'progress' => 'decimal:2',
        'unlocked_at' => 'datetime',
        'is_seen' => 'boolean',
    ];

    /**
     * Get the user that owns the user achievement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the achievement.
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    /**
     * Check if the achievement is unlocked.
     */
    public function isUnlocked(): Attribute
    {
        return new Attribute(
            get: fn () => ! is_null($this->unlocked_at)
        );
    }

    /**
     * Mark the achievement as unlocked.
     */
    public function unlock(): void
    {
        $this->update([
            'progress' => 100,
            'unlocked_at' => now(),
        ]);
    }
}

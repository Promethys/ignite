<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the categories for the user.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the goals for the user.
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * Get the achievements unlocked by the user.
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot(['progress', 'unlocked_at', 'is_seen'])
            ->withTimestamps();
    }

    /**
     * Get the user's unlocked achievements.
     */
    public function unlockedAchievements(): BelongsToMany
    {
        return $this->achievements()->wherePivotNotNull('unlocked_at');
    }

    /**
     * Get the user's achievements in progress.
     */
    public function achievementsInProgress(): BelongsToMany
    {
        return $this->achievements()->wherePivotNull('unlocked_at');
    }

    /**
     * Get active goals for the user.
     */
    public function activeGoals(): HasMany
    {
        return $this->goals()->where('status', 'in_progress');
    }

    /**
     * Get completed goals for the user.
     */
    public function completedGoals(): HasMany
    {
        return $this->goals()->where('status', 'completed');
    }

    /**
     * Calculate total points earned.
     *
     * @return int
     */
    public function getTotalPointsAttribute(): int
    {
        $goalPoints = $this->goals()->sum('points');
        $achievementPoints = $this->unlockedAchievements()->sum('points_reward');

        return $goalPoints + $achievementPoints;
    }

    /**
     * Calculate user level based on points.
     *
     * @return int
     */
    public function getLevelAttribute(): int
    {
        // Simple level calculation: 1 level per 100 points
        return floor($this->total_points / 100) + 1;
    }
}

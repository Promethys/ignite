<?php

namespace App\Models;

use App\Traits\Models\HasRecentScope;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRecentScope;
    use HasRoles;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'locale',
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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'total_points',
        'level',
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
     */
    public function totalPoints(): Attribute
    {
        $goalPoints = $this->goals()->sum('points');
        $achievementPoints = $this->unlockedAchievements()->sum('points_reward');

        return new Attribute(
            get: fn () => $goalPoints + $achievementPoints,
        );
    }

    /**
     * Calculate user level based on points.
     */
    public function level(): Attribute
    {
        // Simple level calculation: 1 level per 100 points
        return new Attribute(
            get: fn () => floor($this->total_points / 100) + 1,
        );
    }

    /**
     * Determine whether the current user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Determine if the current user can access to the Filament Panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }
}

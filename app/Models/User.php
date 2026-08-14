<?php

namespace App\Models;

use App\Notifications\Auth\ResetPassword;
use App\Notifications\Auth\VerifyEmail;
use App\Traits\Models\HasRecentScope;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class User extends Authenticatable implements FilamentUser, HasLocalePreference, MustVerifyEmail
{
    use HasApiTokens;

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
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'has_password',
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
     *
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the goals for the user.
     *
     * @return HasMany<Goal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * Get the achievements unlocked by the user.
     *
     * @return BelongsToMany<Achievement, $this>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot(['progress', 'unlocked_at', 'is_seen'])
            ->withTimestamps();
    }

    /**
     * Get the user's unlocked achievements.
     *
     * @return BelongsToMany<Achievement, $this>
     */
    public function unlockedAchievements(): BelongsToMany
    {
        return $this->achievements()->wherePivotNotNull('unlocked_at');
    }

    /**
     * Get the user's achievements in progress.
     *
     * @return BelongsToMany<Achievement, $this>
     */
    public function achievementsInProgress(): BelongsToMany
    {
        return $this->achievements()->wherePivotNull('unlocked_at');
    }

    /**
     * @return HasMany<SocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Get active goals for the user.
     *
     * @return HasMany<Goal, $this>
     */
    public function activeGoals(): HasMany
    {
        return $this->goals()->where('status', 'in_progress');
    }

    /**
     * Get completed goals for the user.
     *
     * @return HasMany<Goal, $this>
     */
    public function completedGoals(): HasMany
    {
        return $this->goals()->where('status', 'completed');
    }

    public function hasPassword(): Attribute
    {
        return new Attribute(
            get: fn () => $this->password !== null
        );
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

    public function preferredLocale(): string
    {
        return $this->locale ?? config('app.fallback_locale');
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): bool
    {
        try {
            $this->notify(new VerifyEmail);

            return true;
        } catch (TransportExceptionInterface $exception) {
            Log::error('Error sending email verification notification to user.', [
                'category' => 'auth',
                'user_id' => $this->id,
                'notification' => VerifyEmail::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
            ]);

            return false;
        }
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token)
    {
        try {
            $this->notify(new ResetPassword($token));
        } catch (TransportExceptionInterface $exception) {
            Log::error('Error sending password reset notification to user.', [
                'category' => 'auth',
                'user_id' => $this->id,
                'notification' => ResetPassword::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
            ]);
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Goal extends Model
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'icon',
        'type',
        'target_value',
        'current_value',
        'unit',
        'recurrence',
        'start_date',
        'deadline',
        'completed_at',
        'status',
        'priority',
        'points',
        'is_public',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'start_date' => 'date',
        'deadline' => 'date',
        'completed_at' => 'datetime',
        'points' => 'integer',
        'is_public' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the user that owns the goal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category that the goal belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the entries for the goal.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(GoalEntry::class);
    }

    /**
     * Get the milestones for the goal.
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    /**
     * Calculate the progress percentage.
     *
     * @return float
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_value <= 0) {
            return 0;
        }

        return min(($this->current_value / $this->target_value) * 100, 100);
    }

    /**
     * Check if the goal is overdue.
     *
     * @return bool
     */
    public function isOverdue(): bool
    {
        return $this->deadline &&
            $this->deadline->isPast() &&
            $this->status !== 'completed';
    }

    /**
     * Mark the goal as completed.
     *
     * @return void
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}

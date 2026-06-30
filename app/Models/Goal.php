<?php

namespace App\Models;

use App\Observers\GoalObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

#[ObservedBy(GoalObserver::class)]
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
        'direction',
        'initial_value',
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
        'initial_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'start_date' => 'date',
        'deadline' => 'date',
        'completed_at' => 'datetime',
        'points' => 'integer',
        'is_public' => 'boolean',
        'order' => 'integer',
    ];

    protected $with = [
        'category',
    ];

    protected $appends = [
        'progress_percentage',
        'is_overdue',
        'is_completed',
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

    protected function progressPercentage(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->target_value) {
                    return null;
                }

                if (($this->target_value === $this->initial_value)
                    || ($this->target_value - $this->initial_value === 0)) {
                    return $this->is_completed ? 100 : 0;
                }

                $percentage = (($this->current_value - $this->initial_value) / ($this->target_value - $this->initial_value)) * 100;

                return max($percentage, 0); // Allow value over 100%, but not below zero
                // return min(max($percentage, 0), 100);
            },
        );
    }

    /**
     * Check if the goal is overdue.
     */
    public function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->deadline
                && $this->deadline->isPast()
                && $this->status !== 'completed'
        );
    }

    public function isCompleted(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->status === 'completed')
                && ($this->completed_at !== null)
        );
    }

    /**
     * Mark the goal as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the goal as completed.
     */
    public function updateStatus(string $status): void
    {
        $this->update([
            'status' => $status,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Milestone extends Model
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'goal_id',
        'title',
        'description',
        'target_value',
        'order',
        'is_completed',
        'completed_at',
        'points_reward',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_value' => 'decimal:2',
        'order' => 'integer',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'points_reward' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_reached'
    ];

    /**
     * Get the goal that owns the milestone.
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * Mark the milestone as completed.
     *
     * @return void
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    /**
     * Check if the milestone is reached based on current goal value.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isReached(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->goal->current_value >= $this->target_value,
        );
    }
}

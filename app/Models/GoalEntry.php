<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalEntry extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'goal_id',
        'value',
        'previous_value',
        'note',
        'entry_date',
        'attachment_path',
        'attachment_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'previous_value' => 'decimal:2',
        'entry_date' => 'date',
    ];

    /**
     * Get the goal that owns the entry.
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * Get the increment value.
     *
     * @return float
     */
    public function getIncrementAttribute(): float
    {
        return $this->value - $this->previous_value;
    }
}

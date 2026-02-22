<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'increment_value',
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
     */
    public function incrementValue(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->value - $this->previous_value, 2),
        );
    }
}

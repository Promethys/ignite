<?php

namespace App\Traits\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasRecentScope
{
    #[Scope]
    protected function recent(Builder $query, ?string $column = 'created_at'): void
    {
        $query->whereDate($column, '>', now()->minus(weeks: 1));
    }
}

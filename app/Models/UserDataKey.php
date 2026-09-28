<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserDataKey extends Model
{
    use HasUuids;

    protected $fillable = [
        'wrapped_key',
        'master_key_id',
    ];

    protected $hidden = [
        'wrapped_key',
    ];

    public function getConnectionName(): ?string
    {
        return config('user-data.key_connection');
    }
}

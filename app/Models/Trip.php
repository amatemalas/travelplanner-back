<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    public $fillable = [
        'user_id',
        'title',
        'destination',
        'start_date',
        'end_date',
        'budget',
        'image',
    ];

    // RELATIONS
    public function files(): HasMany
    {
        return $this->hasMany('files');
    }
}

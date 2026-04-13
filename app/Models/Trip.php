<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\Activity;
use App\Models\TripFile;

class Trip extends Model
{
    public $fillable = [
        'user_id',
        'uuid',
        'title',
        'destination',
        'image',
        'start_date',
        'end_date',
        'budget',
    ];

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(TripFile::class);
    }
}

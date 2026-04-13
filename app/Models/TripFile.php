<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Trip;

class TripFile extends Model
{
    public $fillable = [
        'trip_id',
        'name',
        'extension',
        'mime_type',
        'url',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}

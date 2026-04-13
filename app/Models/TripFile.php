<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripFile extends Model
{
    public $fillable = [
        'name',
        'extension',
        'mime_type',
        'url',
        'is_public',
    ];

    public $rules = [
        'name' => 'required',
        'extension' => 'required',
        'mime_type' => 'required',
        'url' => 'required',
    ];

    // RELATIONS
    public function trip(): BelongsTo
    {
        return $this->belongsTo('trip');
    }
}

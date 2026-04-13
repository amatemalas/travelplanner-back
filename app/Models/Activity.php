<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    public $fillable = [
        'trip_id',
        'title',
        'location_url',
        'description',
        'day',
        'price',
        'time_start',
    ];
}

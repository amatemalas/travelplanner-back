<?php

use App\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

Route::resource('trips', TripController::class);

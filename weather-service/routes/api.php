<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::get('/weather', [WeatherController::class, 'getTemperature']);

// Route::middleware('cognito')->group(function () {
//     Route::get('/weather', [WeatherController::class, 'getTemperature']);
// });

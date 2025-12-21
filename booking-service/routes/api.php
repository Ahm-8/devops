<?php

use App\Http\Controllers\BookingsController;
use App\Http\Controllers\RoomsController;
use Illuminate\Support\Facades\Route;

// Public routes (no auth required)
Route::get('/rooms', [RoomsController::class, 'getRoomsByLocation']);
Route::get('/rooms/available', [RoomsController::class, 'getAvailableRooms']);

// Protected routes (Cognito auth required)
Route::middleware('cognito')->group(function () {
    Route::get('/bookings', [BookingsController::class, 'getBookings']);
    Route::get('/bookings/price-breakdown', [BookingsController::class, 'getPriceBreakdown']);
    Route::post('/bookings', [BookingsController::class, 'createBooking']);
    Route::delete('/bookings', [BookingsController::class, 'deleteBooking']);
});

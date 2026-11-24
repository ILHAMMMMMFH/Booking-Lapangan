<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\OwnerManagementController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\VenueController;
use App\Models\SportCategory;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/sport-categories', fn () => response()->json(SportCategory::all()));
Route::get('/venues', [VenueController::class, 'index']);
Route::get('/fields/{field}/schedule', [VenueController::class, 'fieldSchedule']);

// Protected (Sanctum Bearer Token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Transaksi Booking & Payment
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::put('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/payments', [PaymentController::class, 'store']);

    // Khusus Owner
    Route::middleware('role:owner')->group(function () {
        Route::post('/bookings/{booking}/check-in', [BookingController::class, 'checkIn']);
        Route::post('/bookings/{booking}/complete', [BookingController::class, 'complete']);
        Route::patch('/payments/{payment}/verify', [PaymentController::class, 'verify']);

        Route::prefix('owner')->group(function () {
            Route::get('/venues', [OwnerManagementController::class, 'venues']);
            Route::post('/venues', [OwnerManagementController::class, 'storeVenue']);
            Route::put('/venues/{venue}', [OwnerManagementController::class, 'updateVenue']);

            Route::post('/venues/{venue}/fields', [OwnerManagementController::class, 'storeField']);
            Route::put('/fields/{field}', [OwnerManagementController::class, 'updateField']);

            Route::post('/venues/{venue}/equipments', [OwnerManagementController::class, 'storeEquipment']);
            Route::put('/equipments/{equipment}', [OwnerManagementController::class, 'updateEquipment']);

            Route::delete('/venues/{venue}', [OwnerManagementController::class, 'destroyVenue']);
            Route::delete('/fields/{field}', [OwnerManagementController::class, 'destroyField']);
        });
    });
});
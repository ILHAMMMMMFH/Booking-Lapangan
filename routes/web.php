<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\OwnerDashboardController;
use App\Http\Controllers\Web\UserBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('user.venues'));

// Guest Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Publik + Penyewa
Route::get('/venues', [UserBookingController::class, 'venues'])->name('user.venues');

Route::middleware('auth')->group(function () {
    Route::get('/my-bookings', [UserBookingController::class, 'myBookings'])->name('user.bookings');
    Route::post('/bookings', [UserBookingController::class, 'store'])->name('user.bookings.store');
    Route::post('/bookings/{booking}/pay', [UserBookingController::class, 'pay'])->name('user.bookings.pay');
    Route::put('/bookings/{booking}/reschedule', [UserBookingController::class, 'reschedule'])->name('user.bookings.reschedule');
    Route::post('/bookings/{booking}/cancel', [UserBookingController::class, 'cancel'])->name('user.bookings.cancel');
});

// Khusus Owner
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/bookings', [OwnerDashboardController::class, 'bookings'])->name('bookings');
    Route::get('/venues', [OwnerDashboardController::class, 'venues'])->name('venues');
    Route::post('/venues', [OwnerDashboardController::class, 'storeVenue'])->name('venues.store');
    Route::post('/venues/{venue}/fields', [OwnerDashboardController::class, 'storeField'])->name('fields.store');
    Route::post('/venues/{venue}/equipments', [OwnerDashboardController::class, 'storeEquipment'])->name('equipments.store');
    Route::patch('/payments/{payment}/verify', [OwnerDashboardController::class, 'verifyPayment'])->name('payments.verify');
    Route::post('/bookings/{booking}/check-in', [OwnerDashboardController::class, 'checkIn'])->name('bookings.checkin');
    Route::post('/bookings/{booking}/complete', [OwnerDashboardController::class, 'complete'])->name('bookings.complete');
    Route::delete('/venues/{venue}', [OwnerDashboardController::class, 'destroyVenue'])->name('venues.destroy');
    Route::delete('/fields/{field}', [OwnerDashboardController::class, 'destroyField'])->name('fields.destroy');
});
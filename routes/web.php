<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DriverDashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PassengerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::prefix('drivers')->name('drivers.')->middleware('role:driver,admin')->group(function () {
        Route::get('/', [DriverDashboardController::class, 'index'])->name('index');
        Route::get('/{driver}', [DriverDashboardController::class, 'show'])->name('show');
        Route::post('/{driver}/start', [DriverDashboardController::class, 'startTrip'])->name('start');
        Route::post('/{driver}/advance', [DriverDashboardController::class, 'advanceStop'])->name('advance');
        Route::post('/{driver}/location', [DriverDashboardController::class, 'updateLocation'])->name('location');
    });

    Route::prefix('passengers')->name('passengers.')->middleware('role:passenger,admin')->group(function () {
        Route::get('/', [PassengerController::class, 'index'])->name('index');
        Route::post('/waitlist', [PassengerController::class, 'joinWaitlist'])->name('waitlist');
    });
});

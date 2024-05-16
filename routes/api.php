<?php

use App\Http\Controllers\Api\RouteApiController;
use App\Http\Controllers\Api\TripApiController;
use Illuminate\Support\Facades\Route;

Route::get('/routes', [RouteApiController::class, 'index']);
Route::get('/routes/{route}', [RouteApiController::class, 'show']);
Route::get('/routes/{route}/geometry', [RouteApiController::class, 'geometry']);

Route::get('/trips/active', [TripApiController::class, 'active']);

Route::post('/trips/start', [TripApiController::class, 'start']);
Route::post('/trips/{trip}/location', [TripApiController::class, 'updateLocation']);
Route::post('/trips/{trip}/advance', [TripApiController::class, 'advance']);

Route::get('/trips/{trip}', [TripApiController::class, 'show']);

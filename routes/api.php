<?php

use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\PackageController;
use App\Http\Controllers\Api\V1\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('packages', [PackageController::class, 'index']);

    Route::middleware('auth:web')->group(function () {
        Route::post('bookings', [BookingController::class, 'store']);
    });
});

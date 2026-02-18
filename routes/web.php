<?php

use App\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Location API (cascading dropdowns)
Route::prefix('api/locations')->group(function () {
    Route::get('/provinces', [LocationController::class, 'provinces']);
    Route::get('/districts', [LocationController::class, 'districts']);
    Route::get('/ds-divisions', [LocationController::class, 'dsDivisions']);
});

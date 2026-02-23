<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\RegistryController;
use App\Http\Controllers\ReviewController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Location Dropdowns
Route::prefix('locations')->group(function () {
    Route::get('/provinces', [LocationController::class, 'provinces']);
    Route::get('/districts', [LocationController::class, 'districts']);
    Route::get('/ds-divisions', [LocationController::class, 'dsDivisions']);
});

// Registry Data Entry
Route::prefix('registry')->group(function () {
    Route::post('/single', [RegistryController::class, 'storeSingle']);
    Route::post('/upload', [RegistryController::class, 'uploadExcel']);
});

// Maker-Checker Reviews
Route::prefix('reviews')->group(function () {
    Route::get('/pending', [ReviewController::class, 'pending']);
    Route::post('/{id}/approve', [ReviewController::class, 'approve']);
    Route::post('/{id}/reject', [ReviewController::class, 'reject']);
});

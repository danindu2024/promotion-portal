<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\RegistryController;
use App\Http\Controllers\ReviewController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Location Dropdowns — read-only, generous limit
Route::prefix('locations')->middleware('throttle:60,1')->group(function () {
    Route::get('/provinces', [LocationController::class, 'provinces']);
    Route::get('/districts', [LocationController::class, 'districts']);
    Route::get('/ds-divisions', [LocationController::class, 'dsDivisions']);
});

// Registry Data Entry — write operations, tighter limit
Route::prefix('registry')->middleware('throttle:30,1')->group(function () {
    Route::post('/single', [RegistryController::class, 'storeSingle']);
    Route::post('/upload', [RegistryController::class, 'uploadExcel']);
    Route::get('/template', [RegistryController::class, 'downloadTemplate']);

    
    // Rejected Records Management
    Route::get('/rejected', [RegistryController::class, 'getRejected']);
    Route::get('/rejected/{id}', [RegistryController::class, 'getRejectedRecord']);
    Route::post('/rejected/{id}/resubmit', [RegistryController::class, 'resubmitRejected']);
});

// Maker-Checker Reviews — sensitive actions, strict limit
Route::prefix('reviews')->middleware('throttle:30,1')->group(function () {
    Route::get('/pending', [ReviewController::class, 'pending']);
    Route::get('/batch/{batchId}', [ReviewController::class, 'batchDetails']);
    Route::post('/batch/{batchId}/approve', [ReviewController::class, 'approveBatch']);
    Route::post('/{id}/reject', [ReviewController::class, 'reject']);
});

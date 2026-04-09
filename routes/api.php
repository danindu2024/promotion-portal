<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\RegistryController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

// All data routes require authentication
Route::middleware('auth:sanctum')->group(function () {

    // Location Dropdowns — read-only, generous limit
    Route::prefix('locations')->middleware('throttle:60,1')->group(function () {
        Route::get('/provinces', [LocationController::class, 'provinces']);
        Route::get('/districts', [LocationController::class, 'districts']);
        Route::get('/ds-divisions', [LocationController::class, 'dsDivisions']);
    });

    // Registry Data Entry — write operations
    Route::prefix('registry')->group(function () {
        // Standard form submission - 30/min
        Route::post('/single', [RegistryController::class, 'storeSingle'])->middleware('throttle:30,1');

        // Bulk upload - strict 5/min to prevent abuse
        Route::post('/upload', [RegistryController::class, 'uploadExcel'])->middleware('throttle:10,1');

        // Template download - read-only, generous
        Route::get('/template', [RegistryController::class, 'downloadTemplate'])->middleware('throttle:30,1');

        // Export invalid rows for correction
        Route::post('/export-errors', [RegistryController::class, 'exportImportErrors'])->middleware('throttle:30,1');

        // Rejected Records Management
        Route::get('/rejected', [RegistryController::class, 'getRejected'])->middleware('throttle:30,1');
        Route::post('/rejected/{id}/resubmit', [RegistryController::class, 'resubmitRejected'])->middleware('throttle:30,1');

        // Updateable Records
        Route::get('/updateable', [RegistryController::class, 'listUpdateable'])->middleware('throttle:30,1');
        Route::post('/updateable/{id}/submit', [RegistryController::class, 'submitUpdate'])->middleware('throttle:30,1');

        // Main Registry Actions
        Route::prefix('main')->group(function () {
            Route::get('/{id}', [\App\Http\Controllers\MainRegistryController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\MainRegistryController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\MainRegistryController::class, 'destroy']);
        });
    });

    // Maker-Checker Reviews — sensitive actions, strict limit
    Route::prefix('reviews')->middleware('throttle:30,1')->group(function () {
        Route::get('/pending', [ReviewController::class, 'pending']);
        Route::get('/batch/{batchId}', [ReviewController::class, 'batchDetails']);
        Route::post('/batch/{batchId}/approve', [ReviewController::class, 'approveBatch']);
        Route::post('/{id}/reject', [ReviewController::class, 'reject']);
    });

    // Analytics Dashboard — read-only, generous limit
    Route::prefix('analytics')->middleware('throttle:60,1')->group(function () {
        Route::get('/kpis', [AnalyticsController::class, 'getKPIs']);
        Route::get('/sectors', [AnalyticsController::class, 'getSectorDistribution']);
        Route::get('/field-of-work', [AnalyticsController::class, 'getFieldOfWorkDistribution']);
        Route::get('/heatmap', [AnalyticsController::class, 'getHeatmapData']);
        Route::get('/ds-heatmap', [AnalyticsController::class, 'getDsHeatmapData']);
        Route::get('/search', [AnalyticsController::class, 'advancedSearch']);
        Route::get('/search/export', [AnalyticsController::class, 'exportAudience']);
    });
});


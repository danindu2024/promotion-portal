<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserManagementController;

use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        $user = auth()->user();
        return redirect(match ($user->access_level) {
            'data entry' => '/data-entry',
            'validator' => '/review',
            'decision maker' => '/dashboard',
            'admin' => '/admin/users',
            default => '/dashboard',
        });
    });

    Route::get('/dashboard', function () {
        return Inertia::render('Registry/Dashboard');
    })->name('dashboard')->middleware('access:decision maker,admin');

    Route::get('/data-entry', function () {
        return Inertia::render('Registry/DataEntry');
    })->middleware('access:data entry,validator,decision maker,admin');

    Route::get('/review', function () {
        return Inertia::render('Registry/Review');
    })->middleware('access:validator,decision maker,admin');

    Route::middleware('access:admin')->group(function () {
        Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
        Route::get('/api/users', [UserManagementController::class, 'index']);
        Route::post('/api/users', [UserManagementController::class, 'store']);
        Route::put('/api/users/{id}', [UserManagementController::class, 'update']);
        Route::delete('/api/users/{id}', [UserManagementController::class, 'destroy']);
    });
});

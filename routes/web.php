<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserManagementController;

Route::get('/', function () {
    return redirect('/data-entry');
});

Route::get('/dashboard', function () {
    return Inertia::render('Registry/Dashboard');
})->name('dashboard');

Route::get('/data-entry', function () {
    return Inertia::render('Registry/DataEntry');
});

Route::get('/review', function () {
    return Inertia::render('Registry/Review');
});

Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
Route::get('/api/users', [UserManagementController::class, 'index']);
Route::post('/api/users', [UserManagementController::class, 'store']);
Route::put('/api/users/{id}', [UserManagementController::class, 'update']);
Route::delete('/api/users/{id}', [UserManagementController::class, 'destroy']);

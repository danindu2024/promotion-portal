<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect('/data-entry');
});

Route::get('/data-entry', function () {
    return Inertia::render('Registry/DataEntry');
});

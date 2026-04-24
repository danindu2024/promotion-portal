<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    // Standard path (Local development or same-folder deployment)
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
} else {
    // Best Practice path (cPanel split deployment)
    // Assumes core files are in '../promotion_app/' relative to the web root
    require __DIR__.'/../promotion_app/vendor/autoload.php';
    $app = require_once __DIR__.'/../promotion_app/bootstrap/app.php';
}

$app->handleRequest(Request::capture());

<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\BankDepositController;

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

    // Bank Deposit Routes
    Route::get('/bank-deposits', [BankDepositController::class, 'index'])->name('bank-deposits.index');
    Route::prefix('api/bank-deposits')->group(function () {
        Route::post('/', [BankDepositController::class, 'store'])->name('bank-deposits.store')
            ->middleware('throttle:30,1');
        Route::get('/global', [BankDepositController::class, 'all'])->name('bank-deposits.global');
        Route::get('/export', [BankDepositController::class, 'export'])->name('bank-deposits.export');
    });
});

// Deployment helper for cPanel (Shared Hosting without SSH)
Route::get('/maintenance/deploy-migrations/{token}', function ($token) {
    // Basic security token check
    if ($token !== config('app.deploy_token', 'default_secret_token_123')) {
        abort(403, 'Unauthorized deployment access.');
    }

    try {
        echo "Running migrations...<br>";
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        echo "Migrations completed successfully.<br>";

        echo "Creating Admin User...<br>";
        \App\Models\User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
                'access_level' => 'admin',
                'is_active' => true,
                'province' => 'Western',
                'district' => 'Colombo',
                'ds_division' => 'Colombo',
            ]
        );
        echo "Admin user created.<br>";
        
        echo "Creating storage link...<br>";
        try {
            \Illuminate\Support\Facades\Artisan::call('storage:link');
            echo "Storage link created.<br>";
        } catch (\Exception $e) {
            echo "Storage link notice: " . $e->getMessage() . "<br>";
        }

        echo "Clearing cache...<br>";
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        echo "Cache cleared.<br>";

        return "System update successful.";
    } catch (\Exception $e) {
        return "Error during update: " . $e->getMessage();
    }
});

// Dedicated route to run migrations
Route::get('/maintenance/migrate/{token}', function ($token) {
    if ($token !== config('app.deploy_token', 'default_secret_token_123')) {
        abort(403, 'Unauthorized migration access.');
    }

    try {
        echo "Running migrations...<br>";
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        echo "Output: <pre>" . \Illuminate\Support\Facades\Artisan::output() . "</pre>";
        return "Migrations completed successfully.";
    } catch (\Exception $e) {
        return "Error during migration: " . $e->getMessage();
    }
});

Route::get('/debug-user', function () {
    $user = auth()->user();
    if (!$user) return "Not logged in";
    
    return [
        'user_id' => $user->user_id,
        'username' => $user->username,
        'access_level_from_db' => $user->access_level,
        'all_user_data' => $user->toArray(),
    ];
});

Route::get('/maintenance/clear-cache/{token}', function ($token) {
    // Basic security token check
    if ($token !== config('app.deploy_token', 'default_secret_token_123')) {
        abort(403, 'Unauthorized access.');
    }

    try {
        echo "Clearing caches...<br>";
        
        // optimize:clear handles config, view, cache, route, and event caches all at once
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        
        echo "All Laravel caches cleared successfully.<br>";
        return "System cache update complete.";
    } catch (\Exception $e) {
        return "Error clearing cache: " . $e->getMessage();
    }
});

// Dedicated route to fix storage link on cPanel
Route::get('/maintenance/storage-link/{token}', function ($token) {
    if ($token !== config('app.deploy_token', 'default_secret_token_123')) {
        abort(403, 'Unauthorized access.');
    }

    try {
        $storagePath = storage_path('app/public');
        $publicPath = public_path('storage');

        if (file_exists($publicPath)) {
            echo "Existing storage link found at $publicPath. Deleting...<br>";
            if (is_link($publicPath)) {
                unlink($publicPath);
            } else {
                // If it's a real directory, we might not want to delete it blindly, 
                // but usually on Laravel it should be a symlink.
                // On some cPanel setups, users might have manually created a folder.
                return "Error: $publicPath is a directory, not a symlink. Please delete it manually via File Manager.";
            }
        }

        echo "Creating new storage link...<br>";
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        
        return "Storage link created successfully.";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

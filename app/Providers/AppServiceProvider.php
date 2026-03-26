<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Request;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share([
            'auth' => function () {
                $user = auth()->user();
                return [
                    'user' => $user ? [
                        'user_id' => $user->user_id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'access_level' => $user->access_level,
                    ] : null,
                ];
            },
            'flash' => [
                'error' => fn () => session()->get('error'),
            ],
        ]);
    }
}

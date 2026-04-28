<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Log;


class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        Log::info('HandleInertiaRequests sharing data for: ' . $request->url());
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'user_id' => $request->user()->user_id,
                    'name' => $request->user()->name,
                    'username' => $request->user()->username,
                    'access_level' => $request->user()->access_level,
                    'province' => $request->user()->province,
                    'district' => $request->user()->district,
                    'ds_division' => $request->user()->ds_division,
                ] : null,
            ],
            'errors' => function () use ($request) {
                $errors = $request->session()->get('errors');

                return $errors
                    ? $errors->getBag('default')->getMessages()
                    : (object) [];
            },
            'flash' => [
                'error' => fn () => $request->session()->get('error'),
            ],
            // Explicitly pass the CSRF token to Inertia
            'csrf_token' => csrf_token(),
        ];
    }
}

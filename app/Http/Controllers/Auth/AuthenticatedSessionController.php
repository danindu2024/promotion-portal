<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;
use App\Helpers\Logger;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Log successful login to database (non-blocking)
            try {
                Logger::log('AUTH_SUCCESS', 'User logged in successfully', 'AUTH', "Access Level: {$user->access_level}");
            } catch (\Exception $e) {
                // Don't block login if logging fails
            }

            $home = match ($user->access_level) {
                'data entry' => '/data-entry',
                'validator' => '/review',
                'decision maker' => '/dashboard',
                'admin' => '/admin/users',
                default => '/dashboard',
            };

            return redirect()->intended($home);
        }

        $message = 'Invalid Username or Password';

        // Log failed login attempt to audit file
        Logger::log('AUTH_FAILURE', "Failed login attempt for username: {$request->username}", 'AUTH');

        if ($request->header('X-Inertia')) {
            return Inertia::render('Auth/Login', [
                'errors' => [
                    'username' => [$message],
                ],
                'flash' => [
                    'error' => $message,
                ],
            ])->toResponse($request)->setStatusCode(422);
        }

        return back()
            ->withInput($request->only('username', 'remember'))
            ->with('error', $message);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        // Log logout to database before destroying session
        Logger::log('AUTH_LOGOUT', 'User logged out', 'AUTH');

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}

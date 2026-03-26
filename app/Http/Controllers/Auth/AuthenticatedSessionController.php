<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

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
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}

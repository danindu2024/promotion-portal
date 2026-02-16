<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Current
{
    /**
     * Get the currently authenticated user (or mock user).
     */
    public static function user()
    {
        // TODO: Replace with Auth::user() once authentication is integrated
        return User::find(1) ?? User::first(); 
    }

    /**
     * Get the current user ID.
     */
    public static function id()
    {
        // TODO: Replace with Auth::id() once authentication is integrated
        return 1;
    }
}

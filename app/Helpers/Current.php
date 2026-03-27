<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Current
{
    /**
     * Get the currently authenticated user.
     */
    public static function user()
    {
        return Auth::user(); 
    }

    /**
     * Get the current user ID.
     */
    public static function id()
    {
        return Auth::id();
    }
}

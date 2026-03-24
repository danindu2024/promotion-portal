<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json(User::all());
        }
        
        return Inertia::render('Admin/UserManagement', [
            'users' => User::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:6|confirmed',
            'province' => 'required|string',
            'district' => 'required|string',
            'ds_division' => 'required|string',
            'access_level' => 'required|string|in:data entry,validator,decision maker,admin',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->user_id . ',user_id',
            'province' => 'required|string',
            'district' => 'required|string',
            'ds_division' => 'required|string',
            'access_level' => 'required|string|in:data entry,validator,decision maker,admin',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6|confirmed']);
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        return response()->json(['message' => 'User updated successfully', 'user' => $user], 200);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        
        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}

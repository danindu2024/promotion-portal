<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Helpers\Logger;

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
            'access_level' => 'required|string|in:data entry,validator,decision maker,admin',
            // Conditional location validation
            'province' => 'required_if:access_level,data entry,validator|nullable|string',
            'district' => 'required_if:access_level,data entry,validator|nullable|string',
            'ds_division' => 'required_if:access_level,data entry|nullable|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        // Log user creation to database
        Logger::log('USER_CREATE', "Created new user: {$user->username}", 'USER_MGMT', "User ID: {$user->user_id}", [
            'username' => $user->username,
            'access_level' => $user->access_level
        ]);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->user_id . ',user_id',
            'access_level' => 'required|string|in:data entry,validator,decision maker,admin',
            // Conditional location validation
            'province' => 'required_if:access_level,data entry,validator|nullable|string',
            'district' => 'required_if:access_level,data entry,validator|nullable|string',
            'ds_division' => 'required_if:access_level,data entry|nullable|string',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6|confirmed']);
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        // Log user update to database
        Logger::log('USER_UPDATE', "Updated user: {$user->username}", 'USER_MGMT', "User ID: {$user->user_id}", [
            'username' => $user->username,
            'access_level' => $user->access_level
        ]);

        return response()->json(['message' => 'User updated successfully', 'user' => $user], 200);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent hard deletion — the user may be referenced in:
        //   staging_data (uploaded_by / reviewed_by)
        //   main_registry (approved_by / deleted_by)
        //   audit_logs (user_id)
        // Hard-deleting would either violate FK constraints or leave orphan data.
        // Instead: mark as inactive and soft-delete (sets deleted_at).
        $user->update(['is_active' => false]);
        $user->delete(); // sets deleted_at via SoftDeletes trait

        // Log user deactivation to database
        Logger::log('USER_DEACTIVATE', "Deactivated user: {$user->username}", 'USER_MGMT', "User ID: {$user->user_id}");

        return response()->json(['message' => 'User deactivated successfully'], 200);
    }
}

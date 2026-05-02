<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Get all users (doctors)
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Create a new user (doctor)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', Password::defaults()],
            'role' => 'required|in:doctor,admin',
            'phone' => 'nullable|string',
        ]);

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    /**
     * Update a user
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => ['sometimes', Password::defaults()],
            'phone' => 'nullable|string',
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    /**
     * Toggle user active status
     */
    public function toggle($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        return response()->json($user);
    }

    /**
     * Delete a user
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(null, 204);
    }
}

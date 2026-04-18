<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // POST /api/login
    // Both admin and doctor use this same endpoint
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Wrong email or wrong password
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        // Account disabled by admin
        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account has been disabled. Contact admin.',
            ], 403);
        }

        // Remove old tokens so login from two places doesn't stack up
        $user->tokens()->delete();

        // Create token — store the role as an ability inside the token
        $token = $user->createToken(
            'auth-token',
            [$user->role]           // abilities: ['admin'] or ['doctor']
        )->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'role'           => $user->role,
                'specialization' => $user->specialization,
            ],
        ]);
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        // Delete only the current token (this device logs out)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    // GET /api/me
    // Frontend calls this on page load to check if token is still valid
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'role'           => $user->role,
                'specialization' => $user->specialization,
                'phone'          => $user->phone,
            ],
        ]);
    }
}

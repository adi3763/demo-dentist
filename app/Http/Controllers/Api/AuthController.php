<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    // POST /api/login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // withTrashed so soft-deleted accounts can't sneak in
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account has been disabled. Contact admin.',
            ], 403);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth-token', [$user->role])->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    // GET /api/me
    public function me(Request $request)
    {
        $user = $request->user()->load('profile');

        return response()->json(['user' => $user]);
    }

    // ── Forgot Password ──────────────────────────────────────

    // POST /api/forgot-password
    // Step 1: user submits their email → we send OTP
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        // Always return success — don't reveal if email exists or not
        if (! $user) {
            return response()->json([
                'message' => 'If that email exists, an OTP has been sent.',
            ]);
        }

        // Invalidate any existing OTPs for this email
        PasswordResetOtp::where('email', $request->email)->delete();

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordResetOtp::create([
            'email'      => $request->email,
            'otp'        => $otp,
            'expires_at' => now()->addMinutes(15),
            'used'       => false,
        ]);

        // Send OTP via email
        Mail::to($user->email)->send(new OtpMail($otp, $user->name));

        return response()->json([
            'message' => 'If that email exists, an OTP has been sent.',
        ]);
    }

    // POST /api/verify-otp
    // Step 2: user submits the OTP → we confirm it's valid
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $record = PasswordResetOtp::where('email', $request->email)
                                  ->where('otp', $request->otp)
                                  ->latest()
                                  ->first();

        if (! $record || ! $record->isValid()) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        return response()->json([
            'message' => 'OTP verified. You can now reset your password.',
            'email'   => $request->email,
        ]);
    }

    // POST /api/reset-password
    // Step 3: user submits new password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'otp'                   => 'required|string|size:6',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $record = PasswordResetOtp::where('email', $request->email)
                                  ->where('otp', $request->otp)
                                  ->latest()
                                  ->first();

        if (! $record || ! $record->isValid()) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Update password and invalidate all tokens (forces re-login)
        $user->update(['password' => Hash::make($request->password)]);
        $user->tokens()->delete();

        // Mark OTP as used
        $record->update(['used' => true]);

        return response()->json([
            'message' => 'Password reset successfully. Please login with your new password.',
        ]);
    }
}

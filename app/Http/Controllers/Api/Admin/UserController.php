<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class UserController extends Controller
{
    // GET /api/admin/users
    // Admins manage all doctors; doctors can view and filter non-deleted doctors.
    public function index(Request $request)
    {
        $query = User::where('role', 'doctor')
                     ->with('profile');

        if ($request->user()->isAdmin()) {
            $query->withTrashed();         // admin sees deleted ones too
        }

        // Filter: active status, available to admins and doctors
        if ($request->filter === 'active') {
            $query->where('is_active', true);
        }

        if ($request->filter === 'inactive') {
            $query->where('is_active', false);
        }

        // Filter: deleted only, admin only
        if ($request->user()->isAdmin() && $request->filter === 'deleted') {
            $query->onlyTrashed();
        }

        $users = $query->select('id','name','email','phone','is_active','created_at','deleted_at')
                       ->orderBy('created_at', 'desc')
                       ->get();

        return response()->json(['users' => $users]);
    }

    // POST /api/admin/users
    // Admin creates a doctor account
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'email'            => 'required|email|unique:users,email',
            'phone'            => 'nullable|string|max:20',

            // Profile fields — all optional at creation
            'specialization'   => 'nullable|string|max:100',
            'qualification'    => 'nullable|string|max:100',
            'experience_years' => 'nullable|integer|min:0',
            'address'          => 'nullable|string|max:255',
            'city'             => 'nullable|string|max:100',
            'state'            => 'nullable|string|max:100',
            'pincode'          => 'nullable|string|max:10',
            'bio'              => 'nullable|string|max:1000',
            'consultation_fee' => 'nullable|string|max:20',
        ]);

        $defaultPassword = 'Welcome@123';

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'phone'     => $validated['phone'] ?? null,
            'password'  => Hash::make($defaultPassword),
            'role'      => 'doctor',
            'is_active' => true,
        ]);

        // Create the profile row immediately
        DoctorProfile::create([
            'user_id'          => $user->id,
            'specialization'   => $validated['specialization'] ?? null,
            'qualification'    => $validated['qualification'] ?? null,
            'experience_years' => $validated['experience_years'] ?? null,
            'address'          => $validated['address'] ?? null,
            'city'             => $validated['city'] ?? null,
            'state'            => $validated['state'] ?? null,
            'pincode'          => $validated['pincode'] ?? null,
            'bio'              => $validated['bio'] ?? null,
            'consultation_fee' => $validated['consultation_fee'] ?? null,
        ]);

        return response()->json([
            'message'          => 'Doctor account created.',
            'default_password' => $defaultPassword,   // show once to admin
            'user'             => $user->load('profile'),
        ], 201);
    }

    // PATCH /api/admin/users/{id}
    // Admin edits a doctor's account details
    public function update(Request $request, $id)
    {
        $user = User::where('role', 'doctor')->findOrFail($id);

        $validated = $request->validate([
            'name'  => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'sometimes|nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Doctor account updated.',
            'user'    => $user->fresh(),
        ]);
    }

    // PATCH /api/admin/users/{id}/toggle
    // Enable or disable a doctor account
    public function toggle($id)
    {
        $user = User::where('role', 'doctor')->findOrFail($id);

        $user->update(['is_active' => ! $user->is_active]);

        return response()->json([
            'message'   => $user->is_active ? 'Account enabled.' : 'Account disabled.',
            'is_active' => $user->is_active,
        ]);
    }

    // DELETE /api/admin/users/{id}
    // Soft delete — record stays in DB, just hidden
    public function destroy($id)
    {
        $user = User::where('role', 'doctor')->findOrFail($id);

        // Revoke all tokens so doctor is logged out immediately
        $user->tokens()->delete();

        $user->delete();   // SoftDelete — sets deleted_at, does NOT remove row

        return response()->json([
            'message' => 'Doctor account deactivated (soft deleted).',
        ]);
    }

    // PATCH /api/admin/users/{id}/restore
    // Restore a soft-deleted doctor
    public function restore($id)
    {
        $user = User::where('role', 'doctor')
                    ->onlyTrashed()
                    ->findOrFail($id);

        $user->restore();   // clears deleted_at

        return response()->json([
            'message' => 'Doctor account restored.',
            'user'    => $user->fresh(),
        ]);
    }

    // PATCH /api/admin/users/{id}/reset-password
    // Admin resets a specific doctor's password to default
    public function resetToDefault($id)
    {
        $user = User::where('role', 'doctor')->findOrFail($id);

        // Default password = phone number or "Welcome@123"
        $defaultPassword = $user->phone ?? 'Welcome@123';

        $user->update([
            'password' => Hash::make($defaultPassword),
        ]);

        // Revoke all tokens — forces doctor to login with new password
        $user->tokens()->delete();

        return response()->json([
            'message'          => "Password reset to default for {$user->name}.",
            'default_password' => $defaultPassword,   // admin sees this once
        ]);
    }
}

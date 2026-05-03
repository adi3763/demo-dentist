<?php
namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // GET /api/doctor/profile
    // Doctor views their own profile
    public function show(Request $request)
    {
        $profile = DoctorProfile::firstOrCreate(
            ['user_id' => $request->user()->id]
        );

        return response()->json([
            'user'    => $request->user()->only('id','name','email','phone'),
            'profile' => $profile,
        ]);
    }

    // PATCH /api/doctor/profile
    // Doctor updates their own profile
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'sometimes|string|max:100',
            'phone'            => 'sometimes|nullable|string|max:20',
            'address'          => 'sometimes|nullable|string|max:255',
            'city'             => 'sometimes|nullable|string|max:100',
            'state'            => 'sometimes|nullable|string|max:100',
            'pincode'          => 'sometimes|nullable|string|max:10',
            'specialization'   => 'sometimes|nullable|string|max:100',
            'qualification'    => 'sometimes|nullable|string|max:100',
            'experience_years' => 'sometimes|nullable|integer|min:0',
            'bio'              => 'sometimes|nullable|string|max:1000',
            'consultation_fee' => 'sometimes|nullable|string|max:20',
            'languages'        => 'sometimes|nullable|array',
            'available_days'   => 'sometimes|nullable|array',
        ]);

        // Split: name and phone go on users table, rest on doctor_profiles
        $userFields    = collect($validated)->only(['name','phone'])->toArray();
        $profileFields = collect($validated)->except(['name','phone'])->toArray();

        if (! empty($userFields)) {
            $request->user()->update($userFields);
        }

        $profile = DoctorProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $profileFields
        );

        return response()->json([
            'message' => 'Profile updated.',
            'user'    => $request->user()->fresh()->only('id','name','email','phone'),
            'profile' => $profile->fresh(),
        ]);
    }

    // POST /api/doctor/profile/photo
    // Doctor uploads their profile photo
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:5048',  // max 5MB
        ]);

        $profile = DoctorProfile::firstOrCreate(
            ['user_id' => $request->user()->id]
        );

        // Delete old photo if exists
        if ($profile->photo && Storage::disk('public')->exists($profile->photo)) {
            Storage::disk('public')->delete($profile->photo);
        }

        // Store new photo
        $path = $request->file('photo')->store('doctor-photos', 'public');

        $profile->update(['photo' => $path]);

        return response()->json([
            'message'   => 'Photo uploaded.',
            'photo_url' => asset('storage/' . $path),
        ]);
    }

    // PATCH /api/doctor/profile/change-password
    // Doctor changes their own password
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (! \Illuminate\Support\Facades\Hash::check(
            $request->current_password,
            $request->user()->password
        )) {
            return response()->json([
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $request->user()->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        // Revoke other tokens, keep current session active
        $request->user()->tokens()
                ->where('id', '!=', $request->user()->currentAccessToken()->id)
                ->delete();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }
}

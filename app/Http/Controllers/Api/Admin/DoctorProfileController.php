<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Http\Request;

class DoctorProfileController extends Controller
{
    // GET /api/admin/users/{id}/profile
    public function show($id)
    {
        $user = User::where('role', 'doctor')->with('profile')->findOrFail($id);

        return response()->json([
            'user'    => $user->only('id', 'name', 'email', 'phone', 'is_active'),
            'profile' => $user->profile,
        ]);
    }

    // PATCH /api/admin/users/{id}/profile
    // Admin edits a doctor's profile
    public function update(Request $request, $id)
    {
        $user = User::where('role', 'doctor')->findOrFail($id);

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

        $userFields    = collect($validated)->only(['name','phone'])->toArray();
        $profileFields = collect($validated)->except(['name','phone'])->toArray();

        if (! empty($userFields)) {
            $user->update($userFields);
        }

        $profile = DoctorProfile::updateOrCreate(
            ['user_id' => $user->id],
            $profileFields
        );

        return response()->json([
            'message' => "Profile updated for {$user->name}.",
            'user'    => $user->fresh()->only('id','name','email','phone'),
            'profile' => $profile->fresh(),
        ]);
    }
}

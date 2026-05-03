<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\ContactSubmission;
use App\Models\DoctorProfile;
use App\Models\DoctorSchedule;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

class FeedDataController extends Controller
{
    public function __invoke(DatabaseSeeder $seeder)
    {
        $seeder->run();

        $doctor = User::where('email', 'adianand3763@gmail.com')->first();
        $admin = User::where('email', 'anandaditya7004@gmail.com')->first();

        return response()->json([
            'message' => 'Demo data fed successfully.',
            'login'   => [
                'admin' => [
                    'email'    => 'anandaditya7004@gmail.com',
                    'password' => 'password',
                ],
                'doctor' => [
                    'email'    => 'adianand3763@gmail.com',
                    'password' => '7004425667',
                    'phone'    => '7004425667',
                ],
            ],
            'counts' => [
                'users'               => User::count(),
                'services'            => Service::count(),
                'doctor_profiles'     => DoctorProfile::count(),
                'doctor_schedules'    => DoctorSchedule::count(),
                'appointments'        => Appointment::count(),
                'blocked_dates'       => BlockedDate::count(),
                'contact_submissions' => ContactSubmission::count(),
            ],
            'ids' => [
                'admin_id'  => $admin?->id,
                'doctor_id' => $doctor?->id,
            ],
        ]);
    }
}

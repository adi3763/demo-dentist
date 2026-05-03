<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\ContactSubmission;
use App\Models\DoctorProfile;
use App\Models\DoctorSchedule;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::withTrashed()->updateOrCreate(
            ['email' => 'anandaditya7004@gmail.com'],
            [
                'name'      => 'Aditya Anand Admin',
                'password'  => Hash::make('password'),
                'role'      => 'admin',
                'phone'     => null,
                'is_active' => true,
            ]
        );

        $doctor = User::withTrashed()->updateOrCreate(
            ['email' => 'adianand3763@gmail.com'],
            [
                'name'           => 'Dr. Aditya Anand',
                'password'       => Hash::make('7004425667'),
                'role'           => 'doctor',
                'phone'          => '7004425667',
                'specialization' => 'General Dentist',
                'is_active'      => true,
            ]
        );

        $admin->restore();
        $doctor->restore();

        DoctorProfile::updateOrCreate(
            ['user_id' => $doctor->id],
            [
                'address'          => 'Demo Dental Clinic',
                'city'             => 'Patna',
                'state'            => 'Bihar',
                'pincode'          => '800001',
                'specialization'   => 'General Dentist',
                'qualification'    => 'BDS',
                'experience_years' => 5,
                'bio'              => 'Friendly dentist focused on preventive care, painless treatments, and clear patient communication.',
                'consultation_fee' => '500',
                'languages'        => ['English', 'Hindi'],
                'available_days'   => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            ]
        );

        $services = collect([
            [
                'name'             => 'General Checkup',
                'description'      => 'Complete oral health checkup with basic consultation.',
                'price'            => 500,
                'duration_minutes' => 30,
            ],
            [
                'name'             => 'Root Canal',
                'description'      => 'Root canal treatment for infected or painful teeth.',
                'price'            => 3500,
                'duration_minutes' => 60,
            ],
            [
                'name'             => 'Teeth Whitening',
                'description'      => 'Cosmetic whitening treatment for a brighter smile.',
                'price'            => 2500,
                'duration_minutes' => 45,
            ],
            [
                'name'             => 'Dental Cleaning',
                'description'      => 'Scaling and polishing to remove plaque and stains.',
                'price'            => 1200,
                'duration_minutes' => 30,
            ],
            [
                'name'             => 'Tooth Extraction',
                'description'      => 'Safe tooth removal with post-care guidance.',
                'price'            => 1500,
                'duration_minutes' => 30,
            ],
        ])->mapWithKeys(function (array $service) {
            $record = Service::updateOrCreate(
                ['name' => $service['name']],
                $service + ['is_active' => true]
            );

            return [$record->name => $record];
        });

        foreach (range(1, 6) as $day) {
            $start = Carbon::createFromTime(9, 0);
            $end = Carbon::createFromTime(18, 0);

            while ($start->lt($end)) {
                DoctorSchedule::updateOrCreate(
                    [
                        'user_id'     => $doctor->id,
                        'day_of_week' => $day,
                        'start_time'  => $start->format('H:i:s'),
                    ],
                    [
                        'end_time'  => $start->copy()->addMinutes(30)->format('H:i:s'),
                        'is_active' => true,
                    ]
                );

                $start->addMinutes(30);
            }
        }

        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->toDateString();
        $nextTuesday = Carbon::now()->next(Carbon::TUESDAY)->toDateString();
        $nextSaturday = Carbon::now()->next(Carbon::SATURDAY)->toDateString();

        Appointment::updateOrCreate(
            [
                'doctor_id'        => $doctor->id,
                'appointment_date' => $nextMonday,
                'start_time'       => '09:00:00',
            ],
            [
                'patient_name'  => 'Rahul Kumar',
                'patient_phone' => '9876543210',
                'patient_email' => 'rahul.patient@example.com',
                'service_id'    => $services['General Checkup']->id,
                'end_time'      => '09:30:00',
                'status'        => 'confirmed',
                'patient_notes' => 'Routine dental checkup.',
            ]
        );

        Appointment::updateOrCreate(
            [
                'doctor_id'        => $doctor->id,
                'appointment_date' => $nextTuesday,
                'start_time'       => '10:00:00',
            ],
            [
                'patient_name'  => 'Priya Singh',
                'patient_phone' => '9123456780',
                'patient_email' => 'priya.patient@example.com',
                'service_id'    => $services['Root Canal']->id,
                'end_time'      => '10:30:00',
                'status'        => 'pending',
                'patient_notes' => 'Tooth pain on lower right side.',
            ]
        );

        BlockedDate::updateOrCreate(
            [
                'user_id'      => $doctor->id,
                'blocked_date' => $nextSaturday,
            ],
            ['reason' => 'Clinic maintenance']
        );

        ContactSubmission::updateOrCreate(
            ['email' => 'demo.patient@example.com'],
            [
                'name'       => 'Demo Patient',
                'phone'      => '9000000000',
                'service_id' => $services['Teeth Whitening']->id,
                'message'    => 'I want to know the available whitening packages.',
                'ip_address' => '127.0.0.1',
                'status'     => 'new',
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
public function run(): void
{
    \App\Models\User::create([
        'name'     => 'Admin',
        'email'    => 'admin@clinic.com',
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
        'role'     => 'admin',
    ]);

    \App\Models\User::create([
        'name'           => 'Dr. Priya Sharma',
        'email'          => 'doctor@clinic.com',
        'password'       => \Illuminate\Support\Facades\Hash::make('password'),
        'role'           => 'doctor',
        'specialization' => 'General Dentist',
        'phone'          => '+919876543210',
    ]);

    // Services matching your frontend
    foreach (['General Checkup', 'Root Canal', 'Teeth Whitening'] as $name) {
        \App\Models\Service::create(['name' => $name, 'is_active' => true]);
    }

    // Seed doctor's schedule: Mon–Sat, 9am–6pm, 30-min slots
    $doctor = \App\Models\User::where('role', 'doctor')->first();
    foreach (range(1, 6) as $day) {
        $start = \Carbon\Carbon::createFromTime(9, 0);
        $end   = \Carbon\Carbon::createFromTime(18, 0);
        while ($start->lt($end)) {
            \App\Models\DoctorSchedule::create([
                'user_id'     => $doctor->id,
                'day_of_week' => $day,
                'start_time'  => $start->format('H:i:s'),
                'end_time'    => $start->copy()->addMinutes(30)->format('H:i:s'),
                'is_active'   => true,
            ]);
            $start->addMinutes(30);
        }
    }
}
}

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
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── Create or Update Admin ───────────────────────────────────
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
        $admin->restore();

        // ── Create or Update Doctors ─────────────────────────────────
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
        $doctor->restore();

        $docAyush = User::withTrashed()->updateOrCreate(
            ['email' => 'kandwalayush22@gamil.com'],
            [
                'name'           => 'Dr. Ayush Kandwal',
                'password'       => Hash::make('7024934163'),
                'role'           => 'doctor',
                'phone'          => '7024934163',
                'specialization' => 'Orthodontist',
                'is_active'      => true,
            ]
        );
        $docAyush->restore();

        $docRachit = User::withTrashed()->updateOrCreate(
            ['email' => 'makeyourtitsjiggle@gmail.com'],
            [
                'name'           => 'Dr. Rachit',
                'password'       => Hash::make('9001001566'),
                'role'           => 'doctor',
                'phone'          => '9001001566',
                'specialization' => 'Periodontist',
                'is_active'      => true,
            ]
        );
        $docRachit->restore();

        $doctors = [$doctor, $docAyush, $docRachit];

        // ── Create or Update Doctor Profiles ─────────────────────────
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

        DoctorProfile::updateOrCreate(
            ['user_id' => $docAyush->id],
            [
                'address'          => 'Smile Orthodontics Center',
                'city'             => 'Delhi',
                'state'            => 'Delhi',
                'pincode'          => '110001',
                'specialization'   => 'Orthodontist',
                'qualification'    => 'BDS, MDS (Orthodontics)',
                'experience_years' => 8,
                'bio'              => 'Specialist in modern dental braces, clear aligners (Invisalign), and correcting bite alignment for all age groups.',
                'consultation_fee' => '800',
                'languages'        => ['English', 'Hindi', 'Punjabi'],
                'available_days'   => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            ]
        );

        DoctorProfile::updateOrCreate(
            ['user_id' => $docRachit->id],
            [
                'address'          => 'Advanced Dental Implants Hub',
                'city'             => 'Mumbai',
                'state'            => 'Maharashtra',
                'pincode'          => '400001',
                'specialization'   => 'Periodontist',
                'qualification'    => 'BDS, MDS (Periodontics & Implantology)',
                'experience_years' => 10,
                'bio'              => 'Expert in dental implants, laser gum surgery, bone grafting, and cosmetic smile designs.',
                'consultation_fee' => '1000',
                'languages'        => ['English', 'Hindi', 'Marathi'],
                'available_days'   => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            ]
        );

        // ── Create or Update Services ────────────────────────────────
        $servicesData = [
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
            [
                'name'             => 'Invisible Aligners',
                'description'      => 'Clear transparent braces (Invisalign) to straighten teeth.',
                'price'            => 45000,
                'duration_minutes' => 60,
            ],
            [
                'name'             => 'Dental Implants',
                'description'      => 'Permanent titanium tooth replacement for missing teeth.',
                'price'            => 25000,
                'duration_minutes' => 60,
            ]
        ];

        $services = [];
        foreach ($servicesData as $s) {
            $services[] = Service::updateOrCreate(
                ['name' => $s['name']],
                $s + ['is_active' => true]
            );
        }

        // ── Generate/Update Schedules (Timings) ──────────────────────
        foreach ($doctors as $doc) {
            foreach (range(1, 6) as $day) { // Mon to Sat
                $start = Carbon::createFromTime(9, 0);
                $end = Carbon::createFromTime(18, 0);

                while ($start->lt($end)) {
                    DoctorSchedule::updateOrCreate(
                        [
                            'user_id'     => $doc->id,
                            'day_of_week' => $day,
                            'start_time'  => $start->format('H:i:s'),
                        ],
                        [
                            'end_time'    => $start->copy()->addMinutes(30)->format('H:i:s'),
                            'is_active'   => true,
                        ]
                    );

                    $start->addMinutes(30);
                }
            }
        }

        // ── Generate 100 Unique Additional Appointments ──────────────
        $firstNames = ['Aarav', 'Vihaan', 'Vivaan', 'Ananya', 'Diya', 'Kabir', 'Aditya', 'Ishaan', 'Rahul', 'Neha', 'Rohan', 'Siddharth', 'Varun', 'Pooja', 'Karan', 'Simran', 'Amit', 'Sunita', 'Rajesh', 'Suresh', 'Anita', 'Vijay', 'Sanjay', 'Geeta', 'Ramesh', 'Dev', 'Tara', 'Arjun', 'Meera', 'Riya', 'Samir', 'Jaya', 'Mohan', 'Kiran', 'Preeti', 'Vikram'];
        $lastNames = ['Sharma', 'Verma', 'Kumar', 'Singh', 'Gupta', 'Patel', 'Joshi', 'Mehta', 'Nair', 'Rao', 'Choudhury', 'Das', 'Roy', 'Sen', 'Mishra', 'Trivedi', 'Bose', 'Reddy', 'Pillai', 'Rani', 'Yadav', 'Pandey', 'Saxena', 'Kapoor', 'Malhotra', 'Menon', 'Grover', 'Dhillon', 'Chawla', 'Deshmukh'];
        
        $statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'rescheduled'];

        $today = Carbon::today();
        $appointmentsCreated = 0;
        
        // Load existing appointments into $usedSlots to prevent conflicts
        $usedSlots = [];
        $existingAppointments = Appointment::all();
        foreach ($existingAppointments as $appt) {
            $key = "{$appt->doctor_id}_{$appt->appointment_date}_{$appt->start_time}";
            $usedSlots[$key] = true;
        }

        // 1. Seed today's appointments (specifically at least 4 for today list)
        $todayTimes = ['10:00:00', '11:30:00', '14:30:00', '16:00:00'];
        foreach ($todayTimes as $index => $time) {
            $doc = $doctors[$index % count($doctors)];
            
            $key = "{$doc->id}_{$today->toDateString()}_{$time}";
            if (isset($usedSlots[$key])) {
                continue; // Skip if already present in DB
            }

            $srv = $services[array_rand($services)];
            $fName = $firstNames[array_rand($firstNames)];
            $lName = $lastNames[array_rand($lastNames)];
            
            Appointment::create([
                'doctor_id'        => $doc->id,
                'appointment_date' => $today->toDateString(),
                'start_time'       => $time,
                'end_time'         => Carbon::createFromTimeString($time)->addMinutes(30)->format('H:i:s'),
                'patient_name'     => "$fName $lName",
                'patient_phone'    => '9' . str_pad(rand(0, 999999999), 9, '0', STR_PAD_LEFT),
                'patient_email'    => strtolower($fName . '.' . $lName . '@example.com'),
                'service_id'       => $srv->id,
                'status'           => $index === 0 ? 'pending' : 'confirmed',
                'patient_notes'    => 'Urgent dental visit for today.',
            ]);

            $usedSlots[$key] = true;
            $appointmentsCreated++;
        }

        // 2. Generate other appointments until we have added 100 new appointments
        while ($appointmentsCreated < 100) {
            $doc = $doctors[array_rand($doctors)];
            
            // Random date between -15 and +15 days
            $daysOffset = rand(-15, 15);
            $date = Carbon::today()->addDays($daysOffset);
            
            // Ticks only for clinical days (Mon-Sat, dayOfWeek is 1-6)
            if ($date->dayOfWeek === 0) {
                continue;
            }

            // Pick a random time slot between 9:00 AM and 5:30 PM (in 30 min increments)
            $hour = rand(9, 17);
            $minute = rand(0, 1) === 0 ? '00' : '30';
            $time = sprintf('%02d:%s:00', $hour, $minute);

            $key = "{$doc->id}_{$date->toDateString()}_{$time}";
            if (isset($usedSlots[$key])) {
                continue; // Skip if already booked
            }

            $fName = $firstNames[array_rand($firstNames)];
            $lName = $lastNames[array_rand($lastNames)];
            $srv = $services[array_rand($services)];
            
            // Determine status based on past or future
            if ($date->lt($today)) {
                $status = rand(0, 9) === 0 ? 'cancelled' : 'completed';
            } else {
                $status = $statuses[array_rand($statuses)];
            }

            Appointment::create([
                'doctor_id'        => $doc->id,
                'appointment_date' => $date->toDateString(),
                'start_time'       => $time,
                'end_time'         => Carbon::createFromTimeString($time)->addMinutes(30)->format('H:i:s'),
                'patient_name'     => "$fName $lName",
                'patient_phone'    => '9' . str_pad(rand(0, 999999999), 9, '0', STR_PAD_LEFT),
                'patient_email'    => strtolower($fName . '.' . $lName . '@example.com'),
                'service_id'       => $srv->id,
                'status'           => $status,
                'patient_notes'    => rand(0, 1) === 0 ? 'Routine dental procedure.' : null,
            ]);

            $usedSlots[$key] = true;
            $appointmentsCreated++;
        }

        // ── Generate 20 Additional Contact Submissions ───────────────
        $inquiryMessages = [
            'Hello, I want to know about teeth whitening procedures and costs.',
            'What is the consultation fee for a first-time dental checkup?',
            'Do you offer clear/invisible aligners for teenager teeth correction?',
            'My tooth hurts whenever I drink cold water. Do I need root canal?',
            'Are implants safe for elderly patients? My father needs one.',
            'How much time does it take for a complete dental cleaning session?',
            'I have a chipped front tooth. What are the options to fix it?',
            'Do you accept general health insurance for dental extractions?',
            'Looking to book a group appointment for my family. Any discount?',
            'Are you open on Saturdays? What are the clinical timings?'
        ];

        for ($i = 1; $i <= 20; $i++) {
            $fName = $firstNames[array_rand($firstNames)];
            $lName = $lastNames[array_rand($lastNames)];
            $srv = $services[array_rand($services)];
            $message = $inquiryMessages[array_rand($inquiryMessages)];
            
            ContactSubmission::create([
                'name'       => "$fName $lName",
                'email'      => strtolower($fName . '.' . $lName . '@example.com'),
                'phone'      => '9' . str_pad(rand(0, 999999999), 9, '0', STR_PAD_LEFT),
                'service_id' => $srv->id,
                'message'    => $message,
                'ip_address' => '127.0.0.1',
                'status'     => ['new', 'read', 'replied'][rand(0, 2)],
            ]);
        }
    }
}

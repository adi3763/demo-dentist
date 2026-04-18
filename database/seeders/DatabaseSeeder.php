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
    // Admin account
    \App\Models\User::create([
        'name'     => 'Admin',
        'email'    => 'admin@clinic.com',
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
        'role'     => 'admin',
    ]);

    // Doctor account
    \App\Models\User::create([
        'name'           => 'Dr. Priya Sharma',
        'email'          => 'doctor@clinic.com',
        'password'       => \Illuminate\Support\Facades\Hash::make('password'),
        'role'           => 'doctor',
        'specialization' => 'General Dentist',
        'phone'          => '9876543210',
    ]);
}
}

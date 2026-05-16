<?php

namespace Tests\Feature;

use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DefaultDoctorScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_without_custom_schedule_gets_regular_default_slots(): void
    {
        $doctor = $this->createDoctor();

        $response = $this->getJson("/api/slots?doctor_id={$doctor->id}&date=2026-05-18");

        $response->assertOk()
            ->assertJsonPath('using_default_schedule', true)
            ->assertJsonPath('slots.0.start_time', '09:00:00')
            ->assertJsonPath('slots.0.end_time', '09:30:00');
    }

    public function test_default_schedule_keeps_sunday_closed(): void
    {
        $doctor = $this->createDoctor();

        $response = $this->getJson("/api/slots?doctor_id={$doctor->id}&date=2026-05-17");

        $response->assertOk()
            ->assertJsonPath('using_default_schedule', true)
            ->assertJsonPath('available', false)
            ->assertJsonPath('slots', []);
    }

    public function test_custom_schedule_overrides_regular_default_slots(): void
    {
        $doctor = $this->createDoctor();

        DoctorSchedule::create([
            'user_id' => $doctor->id,
            'day_of_week' => 1,
            'start_time' => '11:00:00',
            'end_time' => '11:30:00',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/slots?doctor_id={$doctor->id}&date=2026-05-18");

        $response->assertOk()
            ->assertJsonPath('using_default_schedule', false)
            ->assertJsonCount(1, 'slots')
            ->assertJsonPath('slots.0.start_time', '11:00:00');
    }

    private function createDoctor(): User
    {
        return User::create([
            'name' => 'Test Doctor',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => 'doctor',
            'is_active' => true,
        ]);
    }
}

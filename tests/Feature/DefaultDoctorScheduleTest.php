<?php

namespace Tests\Feature;

use App\Models\DoctorSchedule;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
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

    public function test_doctor_can_edit_a_saved_schedule_slot(): void
    {
        $doctor = $this->createDoctor();
        $slot = DoctorSchedule::create([
            'user_id' => $doctor->id,
            'day_of_week' => 1,
            'start_time' => '11:00:00',
            'end_time' => '11:30:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($doctor, 'sanctum')->patchJson("/api/doctor/schedule/{$slot->id}", [
            'day_of_week' => 2,
            'start_time' => '12:00',
            'end_time' => '12:30',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Slot updated successfully.')
            ->assertJsonPath('slot.day_of_week', 2)
            ->assertJsonPath('slot.start_time', '12:00:00')
            ->assertJsonPath('slot.end_time', '12:30:00');
    }

    public function test_doctor_cannot_edit_slot_to_duplicate_day_and_start_time(): void
    {
        $doctor = $this->createDoctor();

        DoctorSchedule::create([
            'user_id' => $doctor->id,
            'day_of_week' => 1,
            'start_time' => '11:00:00',
            'end_time' => '11:30:00',
            'is_active' => true,
        ]);

        $slot = DoctorSchedule::create([
            'user_id' => $doctor->id,
            'day_of_week' => 1,
            'start_time' => '12:00:00',
            'end_time' => '12:30:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($doctor, 'sanctum')->patchJson("/api/doctor/schedule/{$slot->id}", [
            'day_of_week' => 1,
            'start_time' => '11:00',
            'end_time' => '11:30',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Another slot already uses this day and start time.');
    }

    public function test_doctor_dashboard_only_contains_logged_in_doctor_data(): void
    {
        Carbon::setTestNow('2026-05-18 09:00:00');

        $doctor = $this->createDoctor();
        $otherDoctor = $this->createDoctor();
        $service = Service::create([
            'name' => 'Cleaning',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_name' => 'Patient One',
            'patient_phone' => '9999999999',
            'service_id' => $service->id,
            'appointment_date' => '2026-05-18',
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'status' => 'pending',
        ]);

        Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_name' => 'Patient Two',
            'patient_phone' => '8888888888',
            'appointment_date' => '2026-05-19',
            'start_time' => '11:00:00',
            'end_time' => '11:30:00',
            'status' => 'confirmed',
        ]);

        Appointment::create([
            'doctor_id' => $otherDoctor->id,
            'patient_name' => 'Other Patient',
            'patient_phone' => '7777777777',
            'appointment_date' => '2026-05-18',
            'start_time' => '12:00:00',
            'end_time' => '12:30:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($doctor, 'sanctum')->getJson('/api/doctor/dashboard');

        $response->assertOk()
            ->assertJsonPath('doctor.id', $doctor->id)
            ->assertJsonPath('stats.total_appointments', 2)
            ->assertJsonPath('stats.today_appointments', 1)
            ->assertJsonPath('stats.upcoming_appointments', 2)
            ->assertJsonPath('stats.pending', 1)
            ->assertJsonPath('stats.confirmed', 1)
            ->assertJsonPath('today_appointments.0.patient_name', 'Patient One')
            ->assertJsonPath('upcoming_appointments.0.service', 'Cleaning')
            ->assertJsonPath('schedule.using_default_schedule', true);

        Carbon::setTestNow();
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

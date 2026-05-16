<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Services\DoctorAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected DoctorAvailabilityService $availability) {}

    // GET /api/doctor/dashboard
    public function index(Request $request)
    {
        $doctor = $request->user();
        $today = Carbon::today();
        $doctorAppointments = Appointment::where('doctor_id', $doctor->id);

        $statusCounts = (clone $doctorAppointments)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $todayAppointments = (clone $doctorAppointments)
            ->whereDate('appointment_date', $today)
            ->with('service:id,name')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Appointment $appointment) => $this->appointmentSummary($appointment));

        $upcomingAppointments = (clone $doctorAppointments)
            ->whereDate('appointment_date', '>=', $today)
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('service:id,name')
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->take(5)
            ->get()
            ->map(fn (Appointment $appointment) => $this->appointmentSummary($appointment));

        $nextAppointment = $upcomingAppointments->first();

        $last7Days = collect(range(6, 0))->map(function (int $daysAgo) use ($doctor) {
            $date = Carbon::today()->subDays($daysAgo);

            return [
                'date' => $date->format('d M'),
                'count' => Appointment::where('doctor_id', $doctor->id)
                    ->whereDate('appointment_date', $date)
                    ->count(),
            ];
        });

        $usingDefaultSchedule = ! $this->availability->hasCustomSchedule($doctor->id);
        $todaySlotCount = $this->availability
            ->slotsForDay($doctor->id, $today->dayOfWeek)
            ->count();

        $upcomingBlockedDates = BlockedDate::where('user_id', $doctor->id)
            ->where('blocked_date', '>=', $today)
            ->orderBy('blocked_date')
            ->take(5)
            ->get(['id', 'blocked_date', 'reason']);

        return response()->json([
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'email' => $doctor->email,
                'phone' => $doctor->phone,
                'specialization' => $doctor->specialization,
            ],
            'stats' => [
                'total_appointments' => (clone $doctorAppointments)->count(),
                'today_appointments' => $todayAppointments->count(),
                'upcoming_appointments' => (clone $doctorAppointments)
                    ->whereDate('appointment_date', '>=', $today)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count(),
                'pending' => (int) ($statusCounts['pending'] ?? 0),
                'confirmed' => (int) ($statusCounts['confirmed'] ?? 0),
                'completed' => (int) ($statusCounts['completed'] ?? 0),
                'rejected' => (int) ($statusCounts['rejected'] ?? 0),
                'rescheduled' => (int) ($statusCounts['rescheduled'] ?? 0),
                'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
                'completed_this_month' => (clone $doctorAppointments)
                    ->where('status', 'completed')
                    ->whereMonth('appointment_date', $today->month)
                    ->whereYear('appointment_date', $today->year)
                    ->count(),
            ],
            'schedule' => [
                'using_default_schedule' => $usingDefaultSchedule,
                'today_slot_count' => $todaySlotCount,
                'default_schedule' => $this->availability->defaultSummary(),
                'upcoming_blocked_dates' => $upcomingBlockedDates,
            ],
            'next_appointment' => $nextAppointment,
            'today_appointments' => $todayAppointments,
            'upcoming_appointments' => $upcomingAppointments,
            'chart_last_7_days' => $last7Days,
        ]);
    }

    private function appointmentSummary(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'patient_name' => $appointment->patient_name,
            'patient_phone' => $appointment->patient_phone,
            'patient_email' => $appointment->patient_email,
            'service' => $appointment->service?->name,
            'appointment_date' => $appointment->appointment_date?->toDateString(),
            'start_time' => $appointment->start_time,
            'end_time' => $appointment->end_time,
            'status' => $appointment->status,
            'patient_notes' => $appointment->patient_notes,
        ];
    }
}

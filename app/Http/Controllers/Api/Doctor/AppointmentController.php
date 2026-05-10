<?php
namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function __construct(protected WhatsAppService $whatsapp) {}

    // GET /api/doctor/appointments
    // Doctor sees ONLY their own appointments
    public function index(Request $request)
    {
        $appointments = Appointment::where('doctor_id', $request->user()->id)
            ->with('service')
            ->when($request->date,
                fn($q) => $q->whereDate('appointment_date', $request->date))
            ->when($request->status,
                fn($q) => $q->where('status', $request->status))
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        return response()->json(['appointments' => $appointments]);
    }

    // PATCH /api/doctor/appointments/{id}/approve
    // Doctor approves a pending appointment
    public function approve(Request $request, $id)
    {
        $appointment = Appointment::where('doctor_id', $request->user()->id)
                                  ->where('status', 'pending')
                                  ->findOrFail($id);

        $appointment->update([
            'status'      => 'confirmed',
            'approved_at' => now(),
        ]);

        $appointment->load('service');

        $formattedDate = Carbon::parse($appointment->appointment_date)->format('D, d M Y');
        $formattedTime = Carbon::parse($appointment->start_time)->format('h:i A');
        $serviceName   = $appointment->service?->name ?? 'Dental Appointment';
        $doctorName    = $request->user()->name;

        // ── Notify patient — confirmed ────────────────────────
        $this->whatsapp->send(
            $appointment->patient_phone,
            "✅ Appointment Confirmed!\n\n" .
            "Hello {$appointment->patient_name}, your appointment has been confirmed.\n\n" .
            "🏥 Doctor: {$doctorName}\n" .
            "💊 Service: {$serviceName}\n" .
            "📅 Date: {$formattedDate}\n" .
            "⏰ Time: {$formattedTime}\n\n" .
            "Please arrive 10 minutes early.\n" .
            "See you soon! 😊"
        );

        return response()->json([
            'message'     => 'Appointment confirmed. Patient has been notified.',
            'appointment' => $appointment->fresh(),
        ]);
    }

    // PATCH /api/doctor/appointments/{id}/reject
    // Doctor rejects with a reason
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $appointment = Appointment::where('doctor_id', $request->user()->id)
                                  ->where('status', 'pending')
                                  ->findOrFail($id);

        $appointment->update([
            'status'          => 'rejected',
            'rejected_reason' => $request->reason,
            'rejected_at'     => now(),
        ]);

        $rebookLink  = config('app.frontend_url') . '/book';
        $doctorName  = $request->user()->name;

        // ── Notify patient — rejected with reason ─────────────
        $this->whatsapp->send(
            $appointment->patient_phone,
            "❌ Appointment Request Update\n\n" .
            "Hello {$appointment->patient_name}, unfortunately your appointment " .
            "request could not be confirmed at this time.\n\n" .
            "📋 Reason: {$request->reason}\n\n" .
            "You can reschedule at a different time:\n" .
            "🔗 {$rebookLink}\n\n" .
            "We apologize for the inconvenience."
        );

        return response()->json([
            'message' => 'Appointment rejected. Patient has been notified.',
        ]);
    }

    // PATCH /api/doctor/appointments/{id}/reschedule
    // Doctor reschedules with new date/time and reason
    public function reschedule(Request $request, $id)
    {
        $request->validate([
            'new_date'   => 'required|date|after_or_equal:today',
            'new_time'   => 'required|date_format:H:i',
            'reason'     => 'required|string|max:500',
        ]);

        $appointment = Appointment::where('doctor_id', $request->user()->id)
                                  ->whereIn('status', ['pending', 'confirmed'])
                                  ->findOrFail($id);

        $newStartTime = $request->new_time . ':00';
        $newDate      = $request->new_date;

        // Check new slot is free
        $conflict = Appointment::where('doctor_id', $request->user()->id)
                               ->where('appointment_date', $newDate)
                               ->where('start_time', $newStartTime)
                               ->whereIn('status', ['pending', 'confirmed'])
                               ->where('id', '!=', $id)
                               ->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'The new time slot is already booked.',
            ], 409);
        }

        $appointment->update([
            'status'                 => 'rescheduled',
            'rescheduled_date'       => $newDate,
            'rescheduled_start_time' => $newStartTime,
            'reschedule_reason'      => $request->reason,
        ]);

        $formattedNewDate = Carbon::parse($newDate)->format('D, d M Y');
        $formattedNewTime = Carbon::parse($newStartTime)->format('h:i A');
        $doctorName       = $request->user()->name;
        $rebookLink       = config('app.frontend_url') . '/book';

        // ── Notify patient — rescheduled ──────────────────────
        $this->whatsapp->send(
            $appointment->patient_phone,
            "📅 Appointment Rescheduled\n\n" .
            "Hello {$appointment->patient_name}, your appointment with " .
            "{$doctorName} has been rescheduled.\n\n" .
            "📋 Reason: {$request->reason}\n\n" .
            "New Details:\n" .
            "📅 New Date: {$formattedNewDate}\n" .
            "⏰ New Time: {$formattedNewTime}\n\n" .
            "If this time does not suit you, please rebook:\n" .
            "🔗 {$rebookLink}\n\n" .
            "We apologize for the inconvenience."
        );

        return response()->json([
            'message' => 'Appointment rescheduled. Patient has been notified.',
        ]);
    }

    // PATCH /api/doctor/appointments/{id}/complete
    // Doctor marks appointment as done
    public function markComplete(Request $request, $id)
    {
        $appointment = Appointment::where('doctor_id', $request->user()->id)
                                  ->where('status', 'confirmed')
                                  ->findOrFail($id);

        $appointment->update(['status' => 'completed']);

        return response()->json([
            'message' => 'Appointment marked as completed.',
        ]);
    }
}

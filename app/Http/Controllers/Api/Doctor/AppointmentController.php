<?php
namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function __construct(protected WhatsAppService $whatsapp) {}

    // GET /api/doctor/appointments
    public function index(Request $request)
    {
        $appointments = Appointment::where('doctor_id', $request->user()->id)
            ->with('service')
            ->when($request->date, fn($q) => $q->whereDate('appointment_date', $request->date))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        return response()->json(['appointments' => $appointments]);
    }

    // PATCH /api/doctor/appointments/{id}/complete
    public function markComplete(Request $request, $id)
    {
        $appointment = Appointment::where('doctor_id', $request->user()->id)
                                  ->findOrFail($id);

        $appointment->update(['status' => 'completed']);

        return response()->json(['message' => 'Appointment marked as completed.']);
    }

    // GET /api/doctor/appointments/{id}/reschedule
    public function rescheduleInfo(Request $request, $id)
    {
        Appointment::where('doctor_id', $request->user()->id)
                   ->findOrFail($id);

        return response()->json([
            'message' => 'Method not allowed. Use PATCH /api/doctor/appointments/{id}/reschedule to reschedule an appointment.',
        ], 405);
    }

    // PATCH /api/doctor/appointments/{id}/reschedule
    public function reschedule(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'new_date'   => 'required|date|after_or_equal:today',
            'new_time'   => 'required|date_format:H:i',
            'reason'     => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $appointment = Appointment::where('doctor_id', $request->user()->id)
                                  ->findOrFail($id);

        $newStartTime = $validated['new_time'] . ':00';
        $newDate      = $validated['new_date'];

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
            'rescheduled_date'       => $newDate,
            'rescheduled_start_time' => $newStartTime,
            'reschedule_reason'      => $validated['reason'],
            'status'                 => 'rescheduled',
        ]);

        $formattedDate = Carbon::parse($newDate)->format('D, d M Y');
        $formattedTime = Carbon::parse($newStartTime)->format('h:i A');
        $doctorName    = $request->user()->name;

        $this->whatsapp->send(
            $appointment->patient_phone,
            "Dear {$appointment->patient_name},\n\n" .
            "Your appointment with {$doctorName} has been rescheduled.\n\n" .
            "New Date: {$formattedDate}\n" .
            "New Time: {$formattedTime}\n\n" .
            "Reason: {$validated['reason']}\n\n" .
            "We apologize for the inconvenience."
        );

        return response()->json([
            'message' => 'Appointment rescheduled and patient notified.',
        ]);
    }
}

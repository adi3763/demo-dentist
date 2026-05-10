<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\BlockedDate;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function __construct(protected WhatsAppService $whatsapp) {}

    // POST /api/appointments
    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_id'        => 'required|exists:users,id',
            'patient_name'     => 'required|string|max:100',
            'patient_phone'    => 'required|string|max:20',
            'patient_email'    => 'nullable|email',
            'service_id'       => 'nullable|exists:services,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time'       => 'required|date_format:H:i',
            'patient_notes'    => 'nullable|string|max:500',
        ]);

        $date      = $validated['appointment_date'];
        $startTime = $validated['start_time'] . ':00';
        $doctorId  = $validated['doctor_id'];

        // Check date not blocked
        if (BlockedDate::where('user_id', $doctorId)
                       ->where('blocked_date', $date)
                       ->exists()) {
            return response()->json([
                'message' => 'Doctor is not available on this date.',
            ], 422);
        }

        // Get slot to find end_time
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $slot = DoctorSchedule::where('user_id', $doctorId)
                               ->where('day_of_week', $dayOfWeek)
                               ->where('start_time', $startTime)
                               ->where('is_active', true)
                               ->first();

        if (! $slot) {
            return response()->json([
                'message' => 'This time slot does not exist.',
            ], 422);
        }

        // Check slot not already actively booked
        $alreadyBooked = Appointment::where('doctor_id', $doctorId)
                                    ->where('appointment_date', $date)
                                    ->where('start_time', $startTime)
                                    ->whereIn('status', ['pending', 'confirmed'])
                                    ->exists();

        if ($alreadyBooked) {
            return response()->json([
                'message' => 'This slot is already booked. Please choose another time.',
            ], 409);
        }

        try {
            $appointment = DB::transaction(function () use ($validated, $date, $startTime, $slot) {
                return Appointment::create([
                    'doctor_id'        => $validated['doctor_id'],
                    'patient_name'     => $validated['patient_name'],
                    'patient_phone'    => $validated['patient_phone'],
                    'patient_email'    => $validated['patient_email'] ?? null,
                    'service_id'       => $validated['service_id'] ?? null,
                    'appointment_date' => $date,
                    'start_time'       => $startTime,
                    'end_time'         => $slot->end_time,
                    'status'           => 'pending',   // always starts as pending
                    'patient_notes'    => $validated['patient_notes'] ?? null,
                ]);
            });

            $appointment->load(['doctor', 'service']);

            $formattedDate = Carbon::parse($date)->format('D, d M Y');
            $formattedTime = Carbon::parse($startTime)->format('h:i A');
            $serviceName   = $appointment->service?->name ?? 'Dental Appointment';
            $doctorName    = $appointment->doctor->name;

            // Frontend URL for the booking/reschedule page
            $bookingLink   = config('app.frontend_url') . '/appointments';
            $approveLink   = config('app.frontend_url') . '/doctor/appointments/' . $appointment->id;

            // ── Notify patient — pending confirmation ─────────
            $this->whatsapp->send(
                $appointment->patient_phone,
                "Hello {$appointment->patient_name}! 👋\n\n" .
                "Your appointment request has been received.\n\n" .
                "🏥 Doctor: {$doctorName}\n" .
                "💊 Service: {$serviceName}\n" .
                "📅 Date: {$formattedDate}\n" .
                "⏰ Time: {$formattedTime}\n\n" .
                "Your appointment is currently *pending confirmation*. " .
                "You will receive another message once the doctor confirms.\n\n" .
                "Thank you for choosing us!"
            );

            // ── Notify doctor — new request with approve link ─
            $this->whatsapp->send(
                $appointment->doctor->phone,
                "🔔 New Appointment Request!\n\n" .
                "👤 Patient: {$appointment->patient_name}\n" .
                "📞 Phone: {$appointment->patient_phone}\n" .
                "💊 Service: {$serviceName}\n" .
                "📅 Date: {$formattedDate}\n" .
                "⏰ Time: {$formattedTime}\n" .
                ($appointment->patient_notes
                    ? "📝 Notes: {$appointment->patient_notes}\n"
                    : "") .
                "\nPlease approve or reject:\n" .
                "🔗 {$approveLink}"
            );

            return response()->json([
                'message'        => 'Appointment request submitted. You will be notified once confirmed.',
                'appointment_id' => $appointment->id,
                'status'         => 'pending',
                'date'           => $formattedDate,
                'time'           => $formattedTime,
                'doctor'         => $doctorName,
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'This slot was just taken. Please choose another time.',
                ], 409);
            }
            throw $e;
        }
    }
}

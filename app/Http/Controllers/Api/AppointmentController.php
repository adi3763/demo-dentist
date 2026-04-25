<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\BlockedDate;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function __construct(protected WhatsAppService $whatsapp) {}

    // GET /api/appointments
    public function index()
    {
        return response()->json([
            'message' => 'Method not allowed. Use POST /api/appointments to book an appointment.',
        ], 405);
    }

    // POST /api/appointments
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id'        => 'required|exists:users,id',
            'patient_name'     => 'required|string|max:100',
            'patient_phone'    => 'required|string|max:20',
            'patient_email'    => 'nullable|email',
            'service_id'       => 'nullable|exists:services,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time'       => 'required|date_format:H:i',
            'patient_notes'    => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $date      = $validated['appointment_date'];
        $startTime = $validated['start_time'] . ':00';
        $doctorId  = $validated['doctor_id'];
        $appointmentDateTime = Carbon::parse("{$date} {$startTime}");

        if ($appointmentDateTime->lt(now())) {
            return response()->json([
                'message' => 'You can only book appointments for the current time or a future time.',
            ], 422);
        }

        // Check date not blocked
        if (BlockedDate::where('user_id', $doctorId)
                       ->where('blocked_date', $date)
                       ->exists()) {
            return response()->json([
                'message' => 'Doctor is not available on this date.',
            ], 422);
        }

        // Get the slot to find end_time
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

        // Check slot is free (Layer 1 — application check)
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
            // DB transaction — if anything fails, nothing is saved
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
                    'status'           => 'confirmed',
                    'patient_notes'    => $validated['patient_notes'] ?? null,
                ]);
            });

            // Load relationships for notifications
            $appointment->load(['doctor', 'service']);

            $formattedDate = Carbon::parse($date)->format('D, d M Y');
            $formattedTime = Carbon::parse($startTime)->format('h:i A');
            $serviceName   = $appointment->service?->name ?? 'Dental Appointment';
            $doctorName    = $appointment->doctor->name;

            // WhatsApp to patient
            $this->whatsapp->send(
                $appointment->patient_phone,
                "Hello {$appointment->patient_name}! Your appointment has been confirmed.\n\n" .
                "Doctor: {$doctorName}\n" .
                "Service: {$serviceName}\n" .
                "Date: {$formattedDate}\n" .
                "Time: {$formattedTime}\n\n" .
                "Please arrive 10 minutes early. See you soon!"
            );

            // WhatsApp to doctor
            $this->whatsapp->send(
                $appointment->doctor->phone,
                "New appointment booked!\n\n" .
                "Patient: {$appointment->patient_name}\n" .
                "Phone: {$appointment->patient_phone}\n" .
                "Service: {$serviceName}\n" .
                "Date: {$formattedDate}\n" .
                "Time: {$formattedTime}"
            );

            return response()->json([
                'message'        => 'Appointment booked successfully.',
                'appointment_id' => $appointment->id,
                'date'           => $formattedDate,
                'time'           => $formattedTime,
                'doctor'         => $doctorName,
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            // Layer 2 — DB unique index caught a race condition
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'This slot was just taken. Please choose another time.',
                ], 409);
            }
            throw $e;
        }
    }
}

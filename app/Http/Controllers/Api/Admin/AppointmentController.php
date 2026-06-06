<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function __construct(protected WhatsAppService $whatsapp) {}

    // GET /api/admin/appointments
    // Admin sees ALL appointments from ALL doctors
    public function index(Request $request)
    {
        $appointments = Appointment::with(['doctor:id,name', 'service:id,name'])
            ->when($request->date,
                fn($q) => $q->whereDate('appointment_date', $request->date))
            ->when($request->status,
                fn($q) => $q->where('status', $request->status))
            ->when($request->doctor_id,
                fn($q) => $q->where('doctor_id', $request->doctor_id))
            ->when($request->search,
                fn($q) => $q->where(function ($query) use ($request) {
                    $query->where('patient_name', 'like', '%' . $request->search . '%')
                          ->orWhere('patient_phone', 'like', '%' . $request->search . '%');
                }))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($appointments);
    }

    // GET /api/admin/appointments/{id}
    // Admin views single appointment full detail
    public function show($id)
    {
        $appointment = Appointment::with([
            'doctor:id,name,phone,email',
            'service:id,name,price,duration_minutes',
        ])->findOrFail($id);

        return response()->json([
            'appointment' => $appointment,
        ]);
    }

    // PATCH /api/admin/appointments/{id}
    // Admin force-updates any appointment status
    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,rejected,rescheduled,completed,cancelled',
            'reason' => 'nullable|string|max:500',
            'rescheduled_date' => 'nullable|date|after_or_equal:today',
            'rescheduled_start_time' => 'nullable|date_format:H:i',
        ]);

        $oldStatus = $appointment->status;
        $newStatus = $validated['status'];

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'rejected') {
            $updateData['rejected_reason'] = $validated['reason'] ?? 'Schedule conflict';
            $updateData['rejected_at'] = now();
        } elseif ($newStatus === 'rescheduled') {
            if (!empty($validated['rescheduled_date'])) {
                $updateData['rescheduled_date'] = $validated['rescheduled_date'];
            }
            if (!empty($validated['rescheduled_start_time'])) {
                $updateData['rescheduled_start_time'] = $validated['rescheduled_start_time'] . ':00';
            }
            $updateData['reschedule_reason'] = $validated['reason'] ?? 'Emergency reschedule';
        }

        $appointment->update($updateData);
        $appointment->load(['doctor', 'service']);

        // Send WhatsApp notification if status changed
        if ($oldStatus !== $newStatus) {
            $this->sendWhatsAppNotification($appointment, $newStatus, $validated['reason'] ?? null);
        }

        return response()->json([
            'message'     => 'Appointment updated.',
            'appointment' => $appointment->fresh([
                'doctor:id,name',
                'service:id,name',
            ]),
        ]);
    }

    private function sendWhatsAppNotification(Appointment $appointment, string $status, ?string $reason)
    {
        $formattedDate = Carbon::parse($appointment->appointment_date)->format('D, d M Y');
        $formattedTime = Carbon::parse($appointment->start_time)->format('h:i A');
        $serviceName   = $appointment->service?->name ?? 'Dental Appointment';
        $doctorName    = $appointment->doctor?->name ?? 'Doctor';

        switch ($status) {
            case 'confirmed':
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
                break;

            case 'rejected':
                $rebookLink = config('app.frontend_url') . '/book';
                $rejectReason = $reason ?? $appointment->rejected_reason ?? 'Schedule conflict';
                $this->whatsapp->send(
                    $appointment->patient_phone,
                    "❌ Appointment Request Update\n\n" .
                    "Hello {$appointment->patient_name}, unfortunately your appointment " .
                    "request could not be confirmed at this time.\n\n" .
                    "📋 Reason: {$rejectReason}\n\n" .
                    "You can reschedule at a different time:\n" .
                    "🔗 {$rebookLink}\n\n" .
                    "We apologize for the inconvenience."
                );
                break;

            case 'rescheduled':
                $rescheduledDate = $appointment->rescheduled_date ?? $appointment->appointment_date;
                $rescheduledTime = $appointment->rescheduled_start_time ?? $appointment->start_time;
                $formattedReschedDate = Carbon::parse($rescheduledDate)->format('D, d M Y');
                $formattedReschedTime = Carbon::parse($rescheduledTime)->format('h:i A');
                $rebookLink = rtrim(config('app.frontend_url'), '/');
                $reschedReason = $reason ?? $appointment->reschedule_reason ?? 'Emergency rescheduling';

                $this->whatsapp->send(
                    $appointment->patient_phone,
                    "📅 Appointment Rescheduled\n\n" .
                    "Hello {$appointment->patient_name}, your appointment with " .
                    "{$doctorName} has been rescheduled.\n\n" .
                    "📋 Reason: {$reschedReason}\n\n" .
                    "New Details:\n" .
                    "📅 New Date: {$formattedReschedDate}\n" .
                    "⏰ New Time: {$formattedReschedTime}\n\n" .
                    "If this time does not suit you, please rebook:\n" .
                    "🔗 {$rebookLink}\n\n" .
                    "We apologize for the inconvenience."
                );
                break;

            case 'cancelled':
                $this->whatsapp->send(
                    $appointment->patient_phone,
                    "❌ Appointment Cancelled\n\n" .
                    "Hello {$appointment->patient_name}, your appointment with " .
                    "{$doctorName} on {$formattedDate} at {$formattedTime} has been cancelled.\n\n" .
                    "If you did not request this, please contact us."
                );
                break;
        }
    }

    // DELETE /api/admin/appointments/{id}
    // Admin soft deletes an appointment
    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json([
            'message' => 'Appointment deleted.',
        ]);
    }
}

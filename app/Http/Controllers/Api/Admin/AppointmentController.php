<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
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
        ]);

        $appointment->update($validated);

        return response()->json([
            'message'     => 'Appointment updated.',
            'appointment' => $appointment->fresh([
                'doctor:id,name',
                'service:id,name',
            ]),
        ]);
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

<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlockedDate;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Services\DoctorAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SlotController extends Controller
{
    public function __construct(protected DoctorAvailabilityService $availability) {}

    // GET /api/slots?date=2026-04-21&doctor_id=2
    public function available(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date'      => 'required|date|after_or_equal:today',
            'doctor_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $date     = $validator->validated()['date'];
        $doctorId = $validator->validated()['doctor_id'];
        $requestedDate = Carbon::parse($date);
        $now = now();

        // Check if date is blocked for this doctor
        if (BlockedDate::where('user_id', $doctorId)
                       ->where('blocked_date', $date)
                       ->exists()) {
            return response()->json([
                'slots'   => [],
                'blocked' => true,
                'message' => 'Doctor is not available on this date.',
            ]);
        }

        $dayOfWeek = $requestedDate->dayOfWeek;

        // Use the doctor's custom schedule when present, otherwise fall back
        // to the regular clinic schedule so doctors do not have to set basics.
        $usingDefaultSchedule = ! $this->availability->hasCustomSchedule($doctorId);
        $allSlots = $this->availability->slotsForDay($doctorId, $dayOfWeek);

        if ($allSlots->isEmpty()) {
            return response()->json([
                'slots'    => [],
                'available'=> false,
                'using_default_schedule' => $usingDefaultSchedule,
                'message'  => $usingDefaultSchedule
                    ? 'Doctor is not available on this day.'
                    : 'Doctor has no schedule on this date.',
            ]);
        }

        // Already booked times for this doctor on this date
        $bookedTimes = Appointment::where('doctor_id', $doctorId)
                                  ->where('appointment_date', $date)
                                  ->whereIn('status', ['pending', 'confirmed'])
                                  ->pluck('start_time')
                                  ->toArray();

        $slots = $allSlots->map(function ($slot) use ($bookedTimes, $requestedDate, $now) {
            $slotStart = Carbon::parse($requestedDate->format('Y-m-d') . ' ' . $slot->start_time);
            $isPastSlot = $requestedDate->isToday() && $slotStart->lt($now);
            $isBookedSlot = in_array($slot->start_time, $bookedTimes);

            $status = 'available';
            $message = null;

            if ($isPastSlot) {
                $status = 'past';
                $message = 'This slot time has already passed.';
            } elseif ($isBookedSlot) {
                $status = 'booked';
                $message = 'This slot is already booked.';
            }

            return [
                'start_time' => $slot->start_time,
                'end_time'   => $slot->end_time,
                'label'      => Carbon::parse($slot->start_time)->format('h:i A')
                                . ' - ' .
                                Carbon::parse($slot->end_time)->format('h:i A'),
                'available'  => ! $isPastSlot && ! $isBookedSlot,
                'status'     => $status,
                'message'    => $message,
            ];
        });

        return response()->json([
            'slots' => $slots,
            'using_default_schedule' => $usingDefaultSchedule,
        ]);
    }

    // GET /api/services
    public function services()
    {
        return response()->json([
            'services' => Service::where('is_active', true)->get(),
        ]);
    }

    // GET /api/doctors
    public function doctors()
    {
        return response()->json([
            'doctors' => User::where('role', 'doctor')
                             ->where('is_active', true)
                             ->select('id', 'name', 'specialization')
                             ->get(),
        ]);
    }
}

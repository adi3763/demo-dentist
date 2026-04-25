<?php
namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\BlockedDate;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    // GET /api/doctor/schedule
    // Doctor sees their own schedule
    public function index(Request $request)
    {
        $schedule = DoctorSchedule::where('user_id', $request->user()->id)
                                  ->orderBy('day_of_week')
                                  ->orderBy('start_time')
                                  ->get();

        $blocked = BlockedDate::where('user_id', $request->user()->id)
                              ->where('blocked_date', '>=', today())
                              ->orderBy('blocked_date')
                              ->get();

        return response()->json([
            'schedule'      => $schedule,
            'blocked_dates' => $blocked,
        ]);
    }

    // POST /api/doctor/schedule
    // Doctor adds an available time slot
    public function store(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
        ]);

        // Check for duplicate
        $exists = DoctorSchedule::where('user_id', $request->user()->id)
                                ->where('day_of_week', $validated['day_of_week'])
                                ->where('start_time', $validated['start_time'] . ':00')
                                ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This slot already exists in your schedule.',
            ], 422);
        }

        $slot = DoctorSchedule::create([
            'user_id'     => $request->user()->id,
            'day_of_week' => $validated['day_of_week'],
            'start_time'  => $validated['start_time'] . ':00',
            'end_time'    => $validated['end_time'] . ':00',
            'is_active'   => true,
        ]);

        return response()->json([
            'message' => 'Slot added to your schedule.',
            'slot'    => $slot,
        ], 201);
    }

    // DELETE /api/doctor/schedule/{id}
    // Doctor removes a slot from their schedule
    public function destroy(Request $request, $id)
    {
        $slot = DoctorSchedule::where('user_id', $request->user()->id)
                              ->findOrFail($id);
        $slot->delete();

        return response()->json(['message' => 'Slot removed from schedule.']);
    }

    // POST /api/doctor/blocked-dates
    // Doctor blocks a specific date (holiday, sick day)
    public function blockDate(Request $request)
    {
        $validated = $request->validate([
            'blocked_date' => 'required|date|after_or_equal:today',
            'reason'       => 'nullable|string|max:200',
        ]);

        $blocked = BlockedDate::firstOrCreate(
            [
                'user_id'      => $request->user()->id,
                'blocked_date' => $validated['blocked_date'],
            ],
            ['reason' => $validated['reason'] ?? null]
        );

        return response()->json([
            'message' => 'Date blocked successfully.',
            'blocked' => $blocked,
        ], 201);
    }

    // DELETE /api/doctor/blocked-dates/{id}
    // Doctor unblocks a date
    public function unblockDate(Request $request, $id)
    {
        $blocked = BlockedDate::where('user_id', $request->user()->id)
                              ->findOrFail($id);
        $blocked->delete();

        return response()->json(['message' => 'Date unblocked.']);
    }
}

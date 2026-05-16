<?php
namespace App\Http\Controllers\Api\Doctor;


use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\BlockedDate;
use App\Services\DoctorAvailabilityService;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(protected DoctorAvailabilityService $availability) {}

    // GET /api/doctor/schedule
    // Doctor sees their own schedule
    public function index(Request $request)
    {
        $doctorId = $request->user()->id;
        $usingDefaultSchedule = ! $this->availability->hasCustomSchedule($doctorId);

        $schedule = $usingDefaultSchedule
            ? $this->availability->defaultSlotsForWeek()
            : DoctorSchedule::where('user_id', $doctorId)
                                  ->orderBy('day_of_week')
                                  ->orderBy('start_time')
                                  ->get();

        $blocked = BlockedDate::where('user_id', $doctorId)
                              ->where('blocked_date', '>=', today())
                              ->orderBy('blocked_date')
                              ->get();

        return response()->json([
            'schedule'      => $schedule,
            'blocked_dates' => $blocked,
            'using_default_schedule' => $usingDefaultSchedule,
            'default_schedule' => $this->availability->defaultSummary(),
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

    // PATCH /api/doctor/schedule/{id}
    // Doctor edits an existing saved slot.
    public function update(Request $request, $id)
    {
        $slot = DoctorSchedule::where('user_id', $request->user()->id)
                              ->findOrFail($id);

        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
        ]);

        $startTime = $validated['start_time'] . ':00';
        $endTime = $validated['end_time'] . ':00';

        $exists = DoctorSchedule::where('user_id', $request->user()->id)
                                ->where('day_of_week', $validated['day_of_week'])
                                ->where('start_time', $startTime)
                                ->where('id', '!=', $slot->id)
                                ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Another slot already uses this day and start time.',
            ], 422);
        }

        $slot->update([
            'day_of_week' => $validated['day_of_week'],
            'start_time'  => $startTime,
            'end_time'    => $endTime,
        ]);

        return response()->json([
            'message' => 'Slot updated successfully.',
            'slot'    => $slot,
        ]);
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

    // POST /api/doctor/schedule/bulk
    public function bulkStore(Request $request)
    {
        $request->validate([
            'slots'               => 'required|array|min:1',
            'slots.*.day_of_week' => 'required|integer|between:0,6',
            'slots.*.start_time'  => 'required|date_format:H:i',
            'slots.*.end_time'    => 'required|date_format:H:i',
        ]);

        $created = 0;
        $skipped = 0;

        foreach ($request->slots as $slotData) {
            $exists = DoctorSchedule::where('user_id', $request->user()->id)
                                    ->where('day_of_week', $slotData['day_of_week'])
                                    ->where('start_time', $slotData['start_time'] . ':00')
                                    ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            DoctorSchedule::create([
                'user_id'     => $request->user()->id,
                'day_of_week' => $slotData['day_of_week'],
                'start_time'  => $slotData['start_time'] . ':00',
                'end_time'    => $slotData['end_time'] . ':00',
                'is_active'   => true,
            ]);

            $created++;
        }

        return response()->json([
            'message' => "{$created} slots added, {$skipped} already existed.",
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    // POST /api/doctor/schedule/default
    // Save the regular clinic schedule as editable slots for this doctor.
    public function applyDefault(Request $request)
    {
        $result = $this->availability->createDefaultScheduleForDoctor($request->user()->id);

        return response()->json([
            'message' => "{$result['created']} default slots added, {$result['skipped']} already existed.",
            'created' => $result['created'],
            'skipped' => $result['skipped'],
        ]);
    }

    // PATCH /api/doctor/schedule/{id}/toggle
    public function toggle(Request $request, $id)
    {
        $slot = DoctorSchedule::where('user_id', $request->user()->id)
                              ->findOrFail($id);

        $slot->update(['is_active' => ! $slot->is_active]);

        return response()->json([
            'message'   => $slot->is_active ? 'Slot enabled.' : 'Slot disabled.',
            'is_active' => $slot->is_active,
        ]);
    }

    // DELETE /api/doctor/schedule/clear
    public function clearDay(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
        ]);

        $count = DoctorSchedule::where('user_id', $request->user()->id)
                               ->where('day_of_week', $request->day_of_week)
                               ->delete();

        return response()->json([
            'message' => "{$count} slots removed.",
        ]);
    }

    // GET /api/doctor/blocked-dates
    public function blockedDates(Request $request)
    {
        $blocked = BlockedDate::where('user_id', $request->user()->id)
                              ->where('blocked_date', '>=', today())
                              ->orderBy('blocked_date')
                              ->get();

        return response()->json(['blocked_dates' => $blocked]);
    }

    // POST /api/doctor/blocked-dates/bulk
    public function bulkBlockDates(Request $request)
    {
        $request->validate([
            'dates'          => 'required|array|min:1',
            'dates.*.date'   => 'required|date|after_or_equal:today',
            'dates.*.reason' => 'nullable|string|max:200',
        ]);

        $blocked = 0;
        $skipped = 0;

        foreach ($request->dates as $item) {
            $exists = BlockedDate::where('user_id', $request->user()->id)
                                 ->where('blocked_date', $item['date'])
                                 ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            BlockedDate::create([
                'user_id'      => $request->user()->id,
                'blocked_date' => $item['date'],
                'reason'       => $item['reason'] ?? null,
            ]);

            $blocked++;
        }

        return response()->json([
            'message' => "{$blocked} dates blocked, {$skipped} already existed.",
        ]);
    }
}

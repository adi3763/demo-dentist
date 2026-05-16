<?php

namespace App\Services;

use App\Models\DoctorSchedule;
use Illuminate\Support\Collection;

class DoctorAvailabilityService
{
    private const DEFAULT_WORKING_DAYS = [1, 2, 3, 4, 5, 6];
    private const DEFAULT_PERIODS = [
        ['start' => '09:00:00', 'end' => '13:00:00'],
        ['start' => '14:00:00', 'end' => '17:00:00'],
    ];
    private const DEFAULT_SLOT_MINUTES = 30;

    public function hasCustomSchedule(int $doctorId): bool
    {
        return DoctorSchedule::where('user_id', $doctorId)->exists();
    }

    public function slotsForDay(int $doctorId, int $dayOfWeek): Collection
    {
        if ($this->hasCustomSchedule($doctorId)) {
            return DoctorSchedule::where('user_id', $doctorId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->orderBy('start_time')
                ->get();
        }

        return $this->defaultSlotsForDay($dayOfWeek);
    }

    public function defaultSlotsForWeek(): Collection
    {
        return collect(self::DEFAULT_WORKING_DAYS)
            ->flatMap(fn (int $day) => $this->defaultSlotsForDay($day));
    }

    public function defaultSlotsForDay(int $dayOfWeek): Collection
    {
        if (! in_array($dayOfWeek, self::DEFAULT_WORKING_DAYS, true)) {
            return collect();
        }

        $slots = collect();

        foreach (self::DEFAULT_PERIODS as $period) {
            $start = strtotime($period['start']);
            $end = strtotime($period['end']);

            while ($start < $end) {
                $slotEnd = strtotime('+' . self::DEFAULT_SLOT_MINUTES . ' minutes', $start);

                if ($slotEnd > $end) {
                    break;
                }

                $slots->push((object) [
                    'id' => null,
                    'user_id' => null,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => date('H:i:s', $start),
                    'end_time' => date('H:i:s', $slotEnd),
                    'is_active' => true,
                    'is_default' => true,
                ]);

                $start = $slotEnd;
            }
        }

        return $slots;
    }

    public function defaultSummary(): array
    {
        return [
            'days' => 'Monday to Saturday',
            'hours' => '09:00-13:00 and 14:00-17:00',
            'slot_minutes' => self::DEFAULT_SLOT_MINUTES,
            'closed_days' => ['Sunday'],
        ];
    }

    public function createDefaultScheduleForDoctor(int $doctorId): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($this->defaultSlotsForWeek() as $slot) {
            $exists = DoctorSchedule::where('user_id', $doctorId)
                ->where('day_of_week', $slot->day_of_week)
                ->where('start_time', $slot->start_time)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            DoctorSchedule::create([
                'user_id' => $doctorId,
                'day_of_week' => $slot->day_of_week,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'is_active' => true,
            ]);

            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}

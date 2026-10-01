<?php

namespace App\Services;

use App\Models\Activity;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Month-grid layout shared by the student and admin activity calendars:
 * whole Monday–Sunday weeks covering the month, with each activity placed
 * on every day it runs.
 */
class ActivityCalendar
{
    /** ?month=YYYY-MM, falling back to the current month when absent or malformed. */
    public static function resolveMonth(?string $month): CarbonImmutable
    {
        if ($month && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return CarbonImmutable::createFromFormat('!Y-m', $month)->startOfMonth();
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} first and last instant shown on the grid */
    public static function gridRange(CarbonImmutable $month): array
    {
        return [
            $month->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY),
            $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY),
        ];
    }

    /**
     * @param  Collection<int, Activity>  $activities  already limited to the grid range
     * @return list<list<array{date: CarbonImmutable, key: string, inMonth: bool, isToday: bool, activities: Collection<int, Activity>}>>
     */
    public static function weeks(CarbonImmutable $month, Collection $activities): array
    {
        [$gridStart, $gridEnd] = self::gridRange($month);

        $byDay = [];
        foreach ($activities->sortBy('start_at') as $activity) {
            // An activity ending exactly at midnight doesn't really run on
            // that next day, so don't place it there.
            $end = $activity->end_at->isStartOfDay() && $activity->end_at->gt($activity->start_at)
                ? $activity->end_at->subSecond()
                : $activity->end_at;

            $day = CarbonImmutable::parse($activity->start_at)->max($gridStart)->startOfDay();
            $last = CarbonImmutable::parse($end)->min($gridEnd);

            for (; $day->lte($last); $day = $day->addDay()) {
                $byDay[$day->toDateString()][] = $activity;
            }
        }

        $today = CarbonImmutable::today()->toDateString();
        $weeks = [];

        for ($i = 0, $day = $gridStart; $day->lte($gridEnd); $i++, $day = $day->addDay()) {
            $key = $day->toDateString();
            $weeks[intdiv($i, 7)][] = [
                'date' => $day,
                'key' => $key,
                'inMonth' => $day->month === $month->month,
                'isToday' => $key === $today,
                'activities' => collect($byDay[$key] ?? []),
            ];
        }

        return $weeks;
    }
}

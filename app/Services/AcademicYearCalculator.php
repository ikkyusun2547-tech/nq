<?php

namespace App\Services;

use Carbon\CarbonInterface;

class AcademicYearCalculator
{
    /**
     * Thai academic year (พ.ศ.) a given date falls into. Semester 1
     * (Jun-Oct), semester 2 (Nov-Mar) and semester 3/summer (Apr-May)
     * all belong to the academic year that started the previous June —
     * mirrors the logic used to backfill activities.academic_year.
     */
    public static function forDate(CarbonInterface $date): int
    {
        $buddhistYear = $date->year + 543;

        return $buddhistYear - ($date->month >= 6 ? 0 : 1);
    }

    /**
     * [start, end] Carbon instances (inclusive) spanning the given Thai
     * academic year in Gregorian dates, for whereBetween() queries.
     *
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    public static function rangeFor(int $academicYear): array
    {
        $gregorianStart = $academicYear - 543;

        return [
            \Illuminate\Support\Carbon::create($gregorianStart, 6, 1)->startOfDay(),
            \Illuminate\Support\Carbon::create($gregorianStart + 1, 5, 31)->endOfDay(),
        ];
    }
}

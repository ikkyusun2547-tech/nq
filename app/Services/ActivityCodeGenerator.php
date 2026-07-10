<?php

namespace App\Services;

use App\Models\Activity;

class ActivityCodeGenerator
{
    /**
     * Digit assigned to each of the 5 activity categories, in the same order
     * they're presented throughout the UI (ทำนุบำรุงศิลปวัฒนธรรม, วิชาการ,
     * กีฬาและส่งเสริมสุขภาพ, จิตอาสา, คุณธรรมจริยธรรม).
     */
    private const CATEGORY_DIGITS = [
        'culture' => 1,
        'academic' => 2,
        'sports' => 3,
        'volunteer' => 4,
        'ethics' => 5,
    ];

    /**
     * Next running sequence number for the given academic year. Locks
     * existing rows for that year until the caller's transaction commits, so
     * two activities created at the same moment can't collide on the same
     * number. Based on MAX() rather than COUNT() so a deleted activity never
     * frees up its number for reuse.
     */
    public function nextSequence(int $academicYear): int
    {
        return (int) Activity::where('academic_year', $academicYear)
            ->lockForUpdate()
            ->max('activity_seq') + 1;
    }

    /**
     * Format: SRRU + 2-digit academic year (พ.ศ.) + 1-digit category +
     * 3-digit sequence. e.g. SRRU6903001 = ปีการศึกษา 2569, กีฬาฯ, ลำดับ 1.
     */
    public function format(int $academicYear, string $category, int $sequence): string
    {
        return sprintf(
            'SRRU%02d%d%03d',
            $academicYear % 100,
            self::CATEGORY_DIGITS[$category] ?? 0,
            $sequence,
        );
    }
}

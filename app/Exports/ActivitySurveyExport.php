<?php

namespace App\Exports;

use App\Models\Activity;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * One sheet laid out like a printed survey-results table: header facts,
 * the x̄ / S.D. / interpretation table, then the anonymous comments.
 */
class ActivitySurveyExport implements FromArray, ShouldAutoSize
{
    public function __construct(private Activity $activity, private array $summary) {}

    public function array(): array
    {
        $s = $this->summary;
        $rows = [
            [__('ผลการประเมินความพึงพอใจกิจกรรม')],
            [__('กิจกรรม'), $this->activity->title],
            [__('วันที่'), $this->activity->start_at->format('d/m/Y')],
            [__('ผู้ตอบแบบประเมิน'), $s['respondents'].' / '.$s['attendees']],
            [],
            [__('ข้อ'), __('รายการประเมิน'), 'x̄', 'S.D.', __('ระดับ')],
        ];

        foreach ($s['questions'] as $i => $q) {
            $rows[] = [$i + 1, $q['text'], $q['mean'], $q['sd'], __($q['level'])];
        }

        if ($s['overall']) {
            $rows[] = ['', __('รวม'), $s['overall']['mean'], $s['overall']['sd'], __($s['overall']['level'])];
        }

        $rows[] = [];
        $rows[] = [__('ข้อเสนอแนะ')];
        foreach ($s['comments'] as $i => $response) {
            $rows[] = [$i + 1, $response->comment];
        }

        return $rows;
    }
}

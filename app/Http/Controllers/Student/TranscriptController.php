<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CreditTransferRequest;
use App\Models\ExternalActivityRequest;
use App\Services\ActivityEvaluationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TranscriptController extends Controller
{
    private const CATEGORY_LABELS = [
        'culture' => 'ทำนุบำรุงศิลปวัฒนธรรม',
        'academic' => 'วิชาการ',
        'sports' => 'กีฬาและส่งเสริมสุขภาพ',
        'volunteer' => 'จิตอาสา/บำเพ็ญประโยชน์',
        'ethics' => 'คุณธรรมจริยธรรม',
    ];

    /** Matches the category dot colors used on the web dashboard. */
    private const CATEGORY_COLORS = [
        'culture' => '#38bdf8',
        'academic' => '#10b981',
        'sports' => '#fbbf24',
        'volunteer' => '#8b5cf6',
        'ethics' => '#e879f9',
    ];

    private const POSITION_LABELS = [
        'student_council_president' => 'นายกองค์การบริหารนักศึกษา',
        'student_club_president' => 'นายกสโมสรนักศึกษา',
        'student_parliament_president' => 'ประธานสภานักศึกษา',
        'club_president' => 'ประธานชมรม',
        'dormitory_president' => 'ประธานหอพักมหาวิทยาลัย',
        'class_leader' => 'หัวหน้าหมู่เรียน',
        'class_representative' => 'ตัวแทนหมู่เรียน',
    ];

    public function download(Request $request, ActivityEvaluationService $evaluator)
    {
        $user = $request->user();
        $summary = $evaluator->summarize($user);

        $checkins = Attendance::query()
            ->join('activities', 'activities.id', '=', 'attendances.activity_id')
            ->where('attendances.user_id', $user->id)
            ->where('attendances.status', 'auto_approved')
            ->selectRaw('activities.title as title, activities.activity_category as category, attendances.checkin_time as event_date, COALESCE(attendances.credited_hours, activities.credit_hours) as hours')
            ->get()
            ->map(fn ($row) => (object) [
                'title' => $row->title,
                'category' => $row->category,
                'date' => \Illuminate\Support\Carbon::parse($row->event_date),
                'hours' => (int) $row->hours,
                'source' => 'กิจกรรมภายใน',
            ]);

        $externalActivities = ExternalActivityRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->get()
            ->map(fn ($row) => (object) [
                'title' => $row->title,
                'category' => $row->activity_category,
                'date' => $row->activity_date,
                'hours' => $row->hours_credited,
                'source' => 'กิจกรรมภายนอก',
            ]);

        $creditTransfers = CreditTransferRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->get()
            ->map(fn ($row) => (object) [
                'title' => self::POSITION_LABELS[$row->position] ?? $row->position,
                'category' => $row->activity_category,
                'date' => $row->created_at,
                'hours' => $row->hours_credited,
                'source' => 'เทียบโอนตำแหน่ง',
            ]);

        $items = $checkins->concat($externalActivities)->concat($creditTransfers)
            ->sortBy('date')
            ->values();

        $pdf = Pdf::loadView('student.transcript-pdf', [
            'user' => $user,
            'summary' => $summary,
            'items' => $items,
            'categoryLabels' => self::CATEGORY_LABELS,
            'categoryColors' => self::CATEGORY_COLORS,
            'headerGradient' => $this->headerGradientDataUri(),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('activity-summary-'.$user->student_id.'-'.now()->format('Ymd').'.pdf');
    }

    /**
     * dompdf in this setup silently drops CSS linear-gradient backgrounds
     * (confirmed empirically — output byte size doesn't change whether one
     * is present or not), but renders a background-image data URI just
     * fine. Pre-rendering the brand gradient as a small raster image is the
     * only way to get it into the PDF at all.
     */
    private function headerGradientDataUri(): string
    {
        $width = 40;
        $height = 1;
        $image = imagecreatetruecolor($width, $height);

        // Same direction/stops as the web app's .brand-gradient class:
        // brand-purple-950 -> brand-purple-800 (55%) -> brand-green-600.
        $stops = [
            [0.0, [0x2e, 0x10, 0x65]],
            [0.55, [0x5b, 0x21, 0xb6]],
            [1.0, [0x05, 0x96, 0x69]],
        ];

        for ($x = 0; $x < $width; $x++) {
            $t = $x / ($width - 1);
            [$r, $g, $b] = $this->interpolateStops($stops, $t);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, $x, 0, $x, $height, $color);
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * @param  array<int, array{0: float, 1: array{0: int, 1: int, 2: int}}>  $stops
     * @return array{0: int, 1: int, 2: int}
     */
    private function interpolateStops(array $stops, float $t): array
    {
        for ($i = 0; $i < count($stops) - 1; $i++) {
            [$startT, $startColor] = $stops[$i];
            [$endT, $endColor] = $stops[$i + 1];

            if ($t >= $startT && $t <= $endT) {
                $localT = $endT > $startT ? ($t - $startT) / ($endT - $startT) : 0;

                return [
                    (int) round($startColor[0] + ($endColor[0] - $startColor[0]) * $localT),
                    (int) round($startColor[1] + ($endColor[1] - $startColor[1]) * $localT),
                    (int) round($startColor[2] + ($endColor[2] - $startColor[2]) * $localT),
                ];
            }
        }

        return end($stops)[1];
    }
}

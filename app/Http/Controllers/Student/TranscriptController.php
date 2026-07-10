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
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('activity-summary-'.$user->student_id.'-'.now()->format('Ymd').'.pdf');
    }
}

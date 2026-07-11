<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardFeedItemResource;
use App\Http\Resources\DashboardSummaryResource;
use App\Models\LateCheckInRequest;
use App\Services\ActivityEvaluationService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Thai labels for CreditTransferRequest::POSITION_HOURS keys — kept in
     * sync with the same map in Student\DashboardController by design (see
     * that controller's docblock for why it isn't a shared class).
     */
    private const POSITION_LABELS = [
        'student_council_president' => 'นายกองค์การบริหารนักศึกษา',
        'student_club_president' => 'นายกสโมสรนักศึกษา',
        'student_parliament_president' => 'ประธานสภานักศึกษา',
        'club_president' => 'ประธานชมรม',
        'dormitory_president' => 'ประธานหอพักมหาวิทยาลัย',
        'class_leader' => 'หัวหน้าหมู่เรียน',
        'class_representative' => 'ตัวแทนหมู่เรียน',
    ];

    public function show(Request $request, ActivityEvaluationService $evaluator)
    {
        $user = $request->user();

        abort_if($user->isAdmin(), 403, 'Admins should use the web admin panel');

        $summary = $evaluator->summarize($user);

        $checkins = $user->attendances()
            ->with('activity')
            ->whereIn('status', ['auto_approved', 'flagged'])
            ->get()
            ->map(fn ($att) => (object) [
                'title' => $att->activity->title,
                'date' => $att->checkin_time,
                'hours' => $att->credited_hours ?? $att->activity->credit_hours,
                'type' => 'checkin',
                'is_approved' => $att->status === 'auto_approved',
                'activity_id' => $att->activity_id,
                'checkin_method' => $att->checkin_method,
                'location_name' => $att->activity->location_name,
                'photo_url' => asset('storage/'.$att->photo_path),
            ]);

        $externalRequests = $user->externalActivityRequests()
            ->whereIn('status', ['approved', 'pending'])
            ->get()
            ->map(fn ($ext) => (object) [
                'title' => $ext->title,
                'date' => $ext->activity_date,
                'hours' => $ext->hours_credited,
                'type' => 'external',
                'is_approved' => $ext->status === 'approved',
            ]);

        $creditTransfers = $user->creditTransferRequests()
            ->whereIn('status', ['approved', 'pending'])
            ->get()
            ->map(fn ($credit) => (object) [
                'title' => __(self::POSITION_LABELS[$credit->position]),
                'date' => $credit->created_at,
                'hours' => $credit->hours_credited,
                'type' => 'credit_transfer',
                'is_approved' => $credit->status === 'approved',
            ]);

        $items = $checkins->concat($externalRequests)->concat($creditTransfers)->sortByDesc('date')->values();

        $approvedActivities = $items->where('is_approved', true)->take(5)->values();
        $pendingActivities = $items->where('is_approved', false)->take(5)->values();

        $rejectedExternal = $user->externalActivityRequests()
            ->where('status', 'rejected')
            ->get()
            ->reject(function ($ext) use ($user) {
                return $user->externalActivityRequests()
                    ->where('title', $ext->title)
                    ->where('organization', $ext->organization)
                    ->whereDate('activity_date', $ext->activity_date)
                    ->whereIn('status', ['pending', 'approved'])
                    ->exists();
            })
            ->map(fn ($ext) => (object) [
                'title' => $ext->title,
                'date' => $ext->activity_date,
                'type' => 'external',
                'reject_reason' => $ext->reject_reason,
            ]);

        $rejectedLateCheckins = LateCheckInRequest::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->whereNotIn('activity_id', function ($query) use ($user) {
                $query->select('activity_id')
                    ->from('late_check_in_requests')
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'approved']);
            })
            ->with('activity')
            ->get()
            ->map(fn ($req) => (object) [
                'title' => $req->activity->title,
                'date' => $req->created_at,
                'type' => 'checkin',
                'activity_id' => $req->activity_id,
                'reject_reason' => $req->reject_reason,
            ]);

        $rejectedCreditTransfers = $user->creditTransferRequests()
            ->where('status', 'rejected')
            ->get()
            ->reject(function ($credit) use ($user) {
                return $user->creditTransferRequests()
                    ->where('academic_year', $credit->academic_year)
                    ->whereIn('status', ['pending', 'approved'])
                    ->exists();
            })
            ->map(fn ($credit) => (object) [
                'title' => __(self::POSITION_LABELS[$credit->position]),
                'date' => $credit->created_at,
                'type' => 'credit_transfer',
                'reject_reason' => $credit->reject_reason,
            ]);

        $rejectedActivities = $rejectedExternal->concat($rejectedLateCheckins)->concat($rejectedCreditTransfers)->sortByDesc('date')->take(5)->values();

        return response()->json([
            'summary' => new DashboardSummaryResource($summary),
            'approved' => DashboardFeedItemResource::collection($approvedActivities),
            'pending' => DashboardFeedItemResource::collection($pendingActivities),
            'rejected' => DashboardFeedItemResource::collection($rejectedActivities),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LateCheckInRequest;
use App\Services\ActivityEvaluationService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request, ActivityEvaluationService $evaluator)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

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

        $items = $checkins->concat($externalRequests)->sortByDesc('date')->values();

        $approvedActivities = $items->where('is_approved', true)->take(5);
        $pendingActivities = $items->where('is_approved', false)->take(5);

        // Only external requests and late check-in requests actually get
        // rejected in practice — a plain Attendance never reaches
        // 'rejected' (only auto_approved or flagged), so there's no
        // equivalent source to pull in here.
        //
        // Resubmission never overwrites the old rejected row (it's always a
        // fresh insert), so once a later attempt for the "same" thing is
        // pending or approved, the earlier rejection is stale news and gets
        // dropped from this list instead of lingering forever.
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

        $rejectedActivities = $rejectedExternal->concat($rejectedLateCheckins)->sortByDesc('date')->take(5)->values();

        return view('student.dashboard', compact('summary', 'approvedActivities', 'pendingActivities', 'rejectedActivities'));
    }
}

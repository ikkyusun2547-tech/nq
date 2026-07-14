<?php

namespace App\Services;

use App\Models\CreditTransferRequest;
use App\Models\LateCheckInRequest;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The student dashboard's "อนุมัติแล้ว / รออนุมัติ / ถูกปฏิเสธ" feeds merge
 * check-ins (Attendance), external-activity requests, and credit-transfer
 * requests into one chronological list per status. Extracted here so the
 * dashboard's top-5 preview and the full "ดูทั้งหมด" history page
 * (Student\ActivityHistoryController) share one merge/dedup implementation
 * instead of drifting apart as two copies.
 */
class StudentActivityFeed
{
    /**
     * Approved and pending check-ins/external requests/credit transfers,
     * newest first. Callers filter on `is_approved` for the two dashboard
     * buckets this covers.
     */
    public function approvedAndPending(User $user): Collection
    {
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
                'flag_reason' => $att->status === 'flagged' ? $att->flagReasonLabel() : null,
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
                'title' => __(CreditTransferRequest::POSITION_LABELS[$credit->position]),
                'date' => $credit->created_at,
                'hours' => $credit->hours_credited,
                'type' => 'credit_transfer',
                'is_approved' => $credit->status === 'approved',
            ]);

        return $checkins->concat($externalRequests)->concat($creditTransfers)->sortByDesc('date')->values();
    }

    /**
     * Rejected items across all four sources, newest first — with stale
     * rejections dropped once a later resubmission for the "same" thing is
     * pending or approved (a resubmission never overwrites the old rejected
     * row; it's always a fresh insert).
     */
    public function rejected(User $user): Collection
    {
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
                'checkin_method' => 'late_request',
                'reject_reason' => $req->reject_reason,
            ]);

        // A real-time/self-report check-in the admin rejected (see
        // Admin\AttendanceController::reject()) — distinct from a rejected
        // LateCheckInRequest above: this one already has an Attendance row
        // (checkin_method realtime/self_report, never late_request).
        $rejectedAttendances = $user->attendances()
            ->where('status', 'rejected')
            ->with('activity')
            ->get()
            ->map(fn ($att) => (object) [
                'title' => $att->activity->title,
                'date' => $att->checkin_time,
                'type' => 'checkin',
                'activity_id' => $att->activity_id,
                'checkin_method' => $att->checkin_method,
                'reject_reason' => $att->reject_reason,
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
                'title' => __(CreditTransferRequest::POSITION_LABELS[$credit->position]),
                'date' => $credit->created_at,
                'type' => 'credit_transfer',
                'reject_reason' => $credit->reject_reason,
            ]);

        return $rejectedExternal
            ->concat($rejectedLateCheckins)
            ->concat($rejectedAttendances)
            ->concat($rejectedCreditTransfers)
            ->sortByDesc('date')
            ->values();
    }
}

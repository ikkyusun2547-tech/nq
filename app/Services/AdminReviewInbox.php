<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CreditTransferPosition;
use App\Models\CreditTransferRequest;
use App\Models\ExternalActivityRequest;
use App\Models\LateCheckInRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Everything waiting on an admin, merged into one newest-first list for the
 * dashboard's "รอคุณตรวจ" inbox. Rows only link to each type's own review
 * page — approving needs the evidence (and sometimes hours/category) that
 * only those pages show, so nothing is approved from here directly.
 */
class AdminReviewInbox
{
    /**
     * @return array{items: Collection<int, object>, counts: array<string, int>}
     */
    public function build(int $perType = 6): array
    {
        $flagged = Attendance::with(['user', 'activity'])
            ->where('status', 'flagged')
            ->latest('checkin_time')
            ->limit($perType)
            ->get()
            ->map(fn (Attendance $att) => (object) [
                'type' => 'flagged',
                'student' => $att->user,
                'title' => $att->activity?->title,
                'detail' => $att->flagReasonLabel(),
                'at' => $att->checkin_time,
                'url' => route('admin.attendance.flagged'),
            ]);

        $external = ExternalActivityRequest::with('user')
            ->where('status', 'pending')
            ->latest('created_at')
            ->limit($perType)
            ->get()
            ->map(fn (ExternalActivityRequest $req) => (object) [
                'type' => 'external',
                'student' => $req->user,
                'title' => $req->title,
                'detail' => trim(__('ขอ :hours ชม.', ['hours' => $req->hours_requested]).' · '.$req->organization, ' ·'),
                'at' => $req->created_at,
                'url' => route('admin.external-activities.index'),
            ]);

        $late = LateCheckInRequest::with(['user', 'activity'])
            ->where('status', 'pending')
            ->latest('created_at')
            ->limit($perType)
            ->get()
            ->map(fn (LateCheckInRequest $req) => (object) [
                'type' => 'late',
                'student' => $req->user,
                'title' => $req->activity?->title,
                'detail' => Str::limit((string) $req->reason, 80),
                'at' => $req->created_at,
                'url' => route('admin.late-checkins.index'),
            ]);

        $positionLabels = CreditTransferPosition::labelsMap();
        $credit = CreditTransferRequest::with('user')
            ->where('status', 'pending')
            ->latest('created_at')
            ->limit($perType)
            ->get()
            ->map(fn (CreditTransferRequest $req) => (object) [
                'type' => 'credit',
                'student' => $req->user,
                'title' => __($positionLabels[$req->position] ?? $req->position),
                'detail' => __('ขอ :hours ชม.', ['hours' => $req->hours_requested]),
                'at' => $req->created_at,
                'url' => route('admin.credit-transfers.index'),
            ]);

        $counts = self::counts();

        return [
            'items' => $flagged->concat($external)->concat($late)->concat($credit)
                ->sortByDesc(fn ($item) => $item->at?->timestamp ?? 0)
                ->values(),
            'counts' => $counts,
        ];
    }

    /**
     * Pending totals per queue. Also drives the red dots in the admin nav,
     * so it stays four plain COUNT queries.
     *
     * @return array<string, int>
     */
    public static function counts(): array
    {
        return [
            'flagged' => Attendance::where('status', 'flagged')->count(),
            'external' => ExternalActivityRequest::where('status', 'pending')->count(),
            'late' => LateCheckInRequest::where('status', 'pending')->count(),
            'credit' => CreditTransferRequest::where('status', 'pending')->count(),
        ];
    }
}

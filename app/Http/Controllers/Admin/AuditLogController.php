<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditTransferRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Every approve/reject action already stamps reviewed_by/reviewed_at on its
 * own record (attendance, external activity, credit transfer, late
 * check-in), but until now the only way to see "what has this admin done"
 * was opening each record type's list separately and reading the reviewer
 * column row by row. This merges those four with App\Models\AuditLog (role
 * changes, bans, faculty/major edits, graduation-criteria updates — actions
 * that don't carry their own reviewed_by trail) into one chronological feed.
 *
 * Built as a single SQL UNION across the 5 source tables (filtered, sorted,
 * and paginated entirely in the database) rather than loading every historical
 * row into a PHP collection first — the previous version's ->get() on all
 * five tables re-read the *entire* history on every page load regardless of
 * which 30 rows were actually displayed, so the page would only get slower
 * as the audit trail grew. This scales with the page size, not the trail's
 * total length.
 */
class AuditLogController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request)
    {
        $reviewerId = $request->filled('reviewer_id') ? (int) $request->input('reviewer_id') : null;
        $nameExpr = 'COALESCE(u.name_thai, u.name)';
        $studentNameExpr = 'COALESCE(su.name_thai, su.name)';

        $attendances = DB::table('attendances as a')
            ->join('activities as act', 'act.id', '=', 'a.activity_id')
            ->join('users as u', 'u.id', '=', 'a.reviewed_by')
            ->join('users as su', 'su.id', '=', 'a.user_id')
            ->whereNotNull('a.reviewed_by')
            ->when($reviewerId, fn ($q) => $q->where('a.reviewed_by', $reviewerId))
            ->selectRaw("
                a.reviewed_by as reviewer_id, {$nameExpr} as reviewer_name,
                CASE WHEN a.status = 'rejected' THEN 'rejected' ELSE 'approved' END as action,
                ? as type_label,
                a.user_id as student_id, {$studentNameExpr} as student_name,
                act.title as title, a.reviewed_at as reviewed_at
            ", [__('เช็คชื่อ')]);

        $externalRequests = DB::table('external_activity_requests as e')
            ->join('users as u', 'u.id', '=', 'e.reviewed_by')
            ->join('users as su', 'su.id', '=', 'e.user_id')
            ->whereNotNull('e.reviewed_by')
            ->when($reviewerId, fn ($q) => $q->where('e.reviewed_by', $reviewerId))
            ->selectRaw("
                e.reviewed_by as reviewer_id, {$nameExpr} as reviewer_name,
                e.status as action,
                ? as type_label,
                e.user_id as student_id, {$studentNameExpr} as student_name,
                e.title as title, e.reviewed_at as reviewed_at
            ", [__('กิจกรรมภายนอก')]);

        // position is a fixed enum (App\Models\CreditTransferRequest::POSITION_LABELS),
        // translated into a SQL CASE so the human label comes back from the
        // same query instead of a second PHP-side lookup per row.
        $positionCase = collect(CreditTransferRequest::POSITION_LABELS)
            ->map(fn ($label, $key) => "WHEN '{$key}' THEN ?")
            ->implode(' ');
        $positionBindings = array_map('__', array_values(CreditTransferRequest::POSITION_LABELS));

        $creditTransfers = DB::table('credit_transfer_requests as c')
            ->join('users as u', 'u.id', '=', 'c.reviewed_by')
            ->join('users as su', 'su.id', '=', 'c.user_id')
            ->whereNotNull('c.reviewed_by')
            ->when($reviewerId, fn ($q) => $q->where('c.reviewed_by', $reviewerId))
            ->selectRaw("
                c.reviewed_by as reviewer_id, {$nameExpr} as reviewer_name,
                c.status as action,
                ? as type_label,
                c.user_id as student_id, {$studentNameExpr} as student_name,
                CASE c.position {$positionCase} ELSE c.position END as title,
                c.reviewed_at as reviewed_at
            ", [__('เทียบโอนตำแหน่ง'), ...$positionBindings]);

        $lateCheckIns = DB::table('late_check_in_requests as l')
            ->join('activities as act', 'act.id', '=', 'l.activity_id')
            ->join('users as u', 'u.id', '=', 'l.reviewed_by')
            ->join('users as su', 'su.id', '=', 'l.user_id')
            ->whereNotNull('l.reviewed_by')
            ->when($reviewerId, fn ($q) => $q->where('l.reviewed_by', $reviewerId))
            ->selectRaw("
                l.reviewed_by as reviewer_id, {$nameExpr} as reviewer_name,
                l.status as action,
                ? as type_label,
                l.user_id as student_id, {$studentNameExpr} as student_name,
                act.title as title, l.reviewed_at as reviewed_at
            ", [__('เช็คชื่อย้อนหลัง')]);

        $adminActions = DB::table('audit_logs as g')
            ->join('users as u', 'u.id', '=', 'g.actor_id')
            ->leftJoin('users as su', 'su.id', '=', 'g.subject_user_id')
            ->when($reviewerId, fn ($q) => $q->where('g.actor_id', $reviewerId))
            ->selectRaw("
                g.actor_id as reviewer_id, {$nameExpr} as reviewer_name,
                g.action as action,
                g.type_label as type_label,
                g.subject_user_id as student_id, {$studentNameExpr} as student_name,
                g.title as title, g.created_at as reviewed_at
            ");

        $union = $attendances
            ->unionAll($externalRequests)
            ->unionAll($creditTransfers)
            ->unionAll($lateCheckIns)
            ->unionAll($adminActions);

        $counts = DB::query()->fromSub($union, 'entries')
            ->selectRaw('action, count(*) as total')
            ->groupBy('action')
            ->pluck('total', 'action');
        $actionCounts = [
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];

        $entries = DB::query()->fromSub($union, 'entries')
            ->orderByDesc('reviewed_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $entries->getCollection()->transform(function ($row) {
            $row->reviewed_at = $row->reviewed_at ? Carbon::parse($row->reviewed_at) : null;

            return $row;
        });

        // Distinct reviewers across all 5 sources, for the filter dropdown —
        // a small id-only union first, then one lookup against `users`,
        // rather than loading every historical row just to dedupe reviewers.
        $reviewerIds = DB::table('attendances')->select('reviewed_by')->whereNotNull('reviewed_by')
            ->unionAll(DB::table('external_activity_requests')->select('reviewed_by')->whereNotNull('reviewed_by'))
            ->unionAll(DB::table('credit_transfer_requests')->select('reviewed_by')->whereNotNull('reviewed_by'))
            ->unionAll(DB::table('late_check_in_requests')->select('reviewed_by')->whereNotNull('reviewed_by'))
            ->unionAll(DB::table('audit_logs')->select('actor_id as reviewed_by'))
            ->pluck('reviewed_by')
            ->unique();

        $reviewers = User::whereIn('id', $reviewerIds)
            ->get(['id', 'name_thai', 'name'])
            ->sortBy(fn ($u) => $u->name_thai ?? $u->name)
            ->values();

        return view('admin.audit-log.index', compact('entries', 'reviewers', 'actionCounts'));
    }
}

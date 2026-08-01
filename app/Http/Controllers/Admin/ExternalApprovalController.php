<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExternalActivityRequest;
use App\Notifications\ExternalActivityRequestReviewed;
use App\Services\SafeNotifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExternalApprovalController extends Controller
{
    /** ?sort= value => real column to order by. Whitelisted so the query string can never inject an arbitrary column/expression into orderBy(). */
    private const SORTABLE = [
        'title' => 'title',
        'activity_category' => 'activity_category',
        'activity_date' => 'activity_date',
        'hours_requested' => 'hours_requested',
        'created_at' => 'created_at',
    ];

    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $sortField = $request->input('sort');
        $sortColumn = self::SORTABLE[$sortField] ?? null;
        $sortDir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        $requestsQuery = ExternalActivityRequest::with(['user.faculty', 'user.major'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name_thai', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('student_id', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('activity_category'), fn ($query) => $query->where('activity_category', $request->input('activity_category')));

        // name/year_level live on the related user, not
        // external_activity_requests — needs an explicit join (with
        // `select(...*)` so the join's own id/created_at/updated_at
        // columns don't collide with this table's).
        if (in_array($sortField, ['name', 'year_level'], true)) {
            $requestsQuery->join('users', 'users.id', '=', 'external_activity_requests.user_id')
                ->select('external_activity_requests.*')
                ->orderBy($sortField === 'name' ? 'users.name_thai' : 'users.year_level', $sortDir);
        } elseif ($sortColumn) {
            $requestsQuery->orderBy($sortColumn, $sortDir);
        } else {
            $requestsQuery->latest('created_at');
        }

        $requests = $requestsQuery->paginate(20)->withQueryString();

        // Tab-pill counts — independent of search/category filters so they
        // always reflect the true size of each bucket, not just the current view.
        $tabCounts = ExternalActivityRequest::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $tabCounts['all'] = $tabCounts->sum();

        return view('admin.external-activities.index', compact('requests', 'status', 'tabCounts'));
    }

    public function approve(Request $request, ExternalActivityRequest $externalActivityRequest)
    {
        abort_if($externalActivityRequest->status !== 'pending', 422, __('คำร้องนี้ถูกดำเนินการไปแล้ว'));

        $validated = $request->validate([
            'hours_approved' => ['nullable', 'integer', 'min:0', 'max:200'],
            'admin_comment' => ['nullable', 'string', 'max:500'],
        ]);

        // Only store an override when it actually differs from what the
        // student asked for — null means "credited as requested" and keeps
        // the common case (no adjustment) free of redundant data.
        $hoursApproved = $validated['hours_approved'] ?? null;
        if ($hoursApproved !== null && $hoursApproved === $externalActivityRequest->hours_requested) {
            $hoursApproved = null;
        }

        $externalActivityRequest->update([
            'status' => 'approved',
            'hours_approved' => $hoursApproved,
            'admin_comment' => $validated['admin_comment'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        SafeNotifier::send($externalActivityRequest->user, new ExternalActivityRequestReviewed($externalActivityRequest));

        return back()->with('status', __('อนุมัติคำร้องสำเร็จ'));
    }

    public function reject(Request $request, ExternalActivityRequest $externalActivityRequest)
    {
        abort_if($externalActivityRequest->status !== 'pending', 422, __('คำร้องนี้ถูกดำเนินการไปแล้ว'));

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:500'],
            'admin_comment' => ['nullable', 'string', 'max:500'],
        ]);

        $externalActivityRequest->update([
            'status' => 'rejected',
            'reject_reason' => $validated['reject_reason'],
            'admin_comment' => $validated['admin_comment'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        SafeNotifier::send($externalActivityRequest->user, new ExternalActivityRequestReviewed($externalActivityRequest));

        return back()->with('status', __('ปฏิเสธคำร้องสำเร็จ'));
    }
}

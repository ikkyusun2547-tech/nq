<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ActivityAttendeesExport;
use App\Exports\ActivityMissingStudentsExport;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Faculty;
use App\Notifications\AttendanceApproved;
use App\Notifications\AttendanceRejected;
use App\Services\DynamicQrTokenGenerator;
use App\Services\SafeNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AttendanceController extends Controller
{
    public function qrDisplay(Activity $activity, DynamicQrTokenGenerator $qrTokens)
    {
        return view('admin.attendance.qr-display', [
            'activity' => $activity,
            'rotationSeconds' => DynamicQrTokenGenerator::WINDOW_SECONDS,
            'scanValidityMinutes' => (int) ceil($qrTokens->scanValiditySeconds() / 60),
            'canCheckIn' => $activity->acceptsCheckIn(),
        ]);
    }

    public function qrFragment(Activity $activity, DynamicQrTokenGenerator $qrTokens)
    {
        // Don't mint a live, scannable token for an activity that can't
        // actually accept a check-in — the QR display page stops polling
        // this once closed, but guard the endpoint itself too.
        if (! $activity->acceptsCheckIn()) {
            return view('admin.attendance.qr-fragment-closed', ['activity' => $activity]);
        }

        $token = $qrTokens->generate($activity);
        $svg = QrCode::size(360)->margin(1)->generate($token);

        return view('admin.attendance.qr-fragment', [
            'svg' => $svg,
            'secondsUntilNextRotation' => $qrTokens->secondsUntilNextRotation(),
            'rotationSeconds' => DynamicQrTokenGenerator::WINDOW_SECONDS,
        ]);
    }

    /**
     * Printable backup QR for when there's no live screen at the venue
     * (dead projector, no signal for the kiosk page). Doesn't rotate, so any
     * check-in made with it always lands as flagged — see
     * AttendanceAutomationService::checkIn().
     */
    public function qrPrint(Activity $activity, DynamicQrTokenGenerator $qrTokens)
    {
        $token = $qrTokens->generateStatic($activity);
        $svg = QrCode::format('svg')->size(420)->margin(1)->generate($token);

        // dompdf doesn't render inline <svg> markup reliably, but handles it
        // fine as a normal image once it's a data URI behind an <img> tag.
        $qrDataUri = 'data:image/svg+xml;base64,'.base64_encode($svg);

        $pdf = Pdf::loadView('admin.attendance.qr-print-pdf', [
            'activity' => $activity,
            'qrDataUri' => $qrDataUri,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('qr-backup-'.($activity->activity_code ?? $activity->id).'.pdf');
    }

    /**
     * Live event control: detailed attendance matrix for one activity.
     */
    public function index(Activity $activity, Request $request)
    {
        $attendances = $activity->attendances()
            ->with(['user.faculty', 'user.major'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name_thai', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('faculty_id'), function ($query) use ($request) {
                $query->whereHas('user', fn ($userQuery) => $userQuery->where('faculty_id', $request->input('faculty_id')));
            })
            ->when($request->filled('major_id'), function ($query) use ($request) {
                $query->whereHas('user', fn ($userQuery) => $userQuery->where('major_id', $request->input('major_id')));
            })
            ->orderByDesc('checkin_time')
            ->get();

        $faculties = Faculty::with(['majors' => fn ($query) => $query->orderBy('name_th')])->orderBy('name_th')->get();

        $requiredCount = $activity->eligibleStudentsCount();
        $checkedInCount = $activity->attendances()->count();
        $missingStudents = $activity->missingStudentsQuery()
            ->with(['faculty', 'major'])
            ->orderBy('student_id')
            ->get();

        return view('admin.attendance.index', compact(
            'activity', 'attendances', 'faculties', 'requiredCount', 'checkedInCount', 'missingStudents'
        ));
    }

    /**
     * Bulk-confirm the selected attendance rows as auto_approved, stamping
     * the reviewing admin. Powers both the "Approve All Valid" button
     * (selection pre-filled with already-valid rows, for an audit trail)
     * and "Force Bypass Selected" (selection is whatever flagged rows the
     * admin manually ticks, e.g. a building-wide GPS outage).
     */
    public function bulkApprove(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'attendance_ids' => ['required', 'array', 'min:1'],
            'attendance_ids.*' => ['integer'],
        ]);

        // Only rows that were actually flagged represent a meaningful status
        // change worth notifying the student about — re-stamping already
        // auto_approved rows (the "Approve All Valid" button) is a no-op.
        $newlyApproved = $activity->attendances()
            ->whereIn('id', $validated['attendance_ids'])
            ->where('status', 'flagged')
            ->with(['user', 'activity'])
            ->get();

        $count = $activity->attendances()
            ->whereIn('id', $validated['attendance_ids'])
            ->update([
                'status' => 'auto_approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

        foreach ($newlyApproved as $attendance) {
            SafeNotifier::send($attendance->user, new AttendanceApproved($attendance));
        }

        return back()->with('status', __('อัปเดตสถานะสำเร็จ :count รายการ', ['count' => $count]));
    }

    /**
     * Cross-activity queue for flagged check-ins — before this, an admin
     * could only review flagged rows one activity at a time (via index()
     * above), and the "flagged attendances" dashboard stat had nowhere to
     * link to. Also the only way to resolve a flagged row with something
     * other than "auto_approved" — reject() below is the one place a
     * plain real-time/self-report check-in can end up rejected.
     */
    public function flaggedIndex(Request $request)
    {
        $status = $request->input('status', 'flagged');
        $status = in_array($status, ['flagged', 'rejected', 'all'], true) ? $status : 'flagged';

        $attendances = Attendance::with(['user.faculty', 'user.major', 'activity'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($status === 'all', fn ($query) => $query->whereIn('status', ['flagged', 'rejected']))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name_thai', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                });
            })
            ->latest('checkin_time')
            ->paginate(20)
            ->withQueryString();

        // Counts for the tab pills — independent of the search box so the
        // numbers always describe "everything in that bucket", not just
        // what's currently filtered into view.
        $tabCounts = [
            'flagged' => Attendance::where('status', 'flagged')->count(),
            'rejected' => Attendance::where('status', 'rejected')->count(),
        ];
        $tabCounts['all'] = $tabCounts['flagged'] + $tabCounts['rejected'];

        return view('admin.attendance.flagged', compact('attendances', 'status', 'tabCounts'));
    }

    public function approve(Request $request, Attendance $attendance)
    {
        // A plain 422 abort here would render Laravel's raw exception page
        // on a normal form POST (this action isn't submitted via fetch/XHR)
        // — a graceful redirect-with-flash keeps a double-click or
        // two-admins-same-item race from ever showing a crash screen.
        if ($attendance->status !== 'flagged') {
            return back()->with('error', __('รายการนี้ถูกดำเนินการไปแล้ว'));
        }

        $attendance->update([
            'status' => 'auto_approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        SafeNotifier::send($attendance->user, new AttendanceApproved($attendance));

        return back()->with('status', __('อนุมัติการเช็คชื่อสำเร็จ'));
    }

    public function reject(Request $request, Attendance $attendance)
    {
        if ($attendance->status !== 'flagged') {
            return back()->with('error', __('รายการนี้ถูกดำเนินการไปแล้ว'));
        }

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:500'],
        ]);

        $attendance->update([
            'status' => 'rejected',
            'reject_reason' => $validated['reject_reason'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        SafeNotifier::send($attendance->user, new AttendanceRejected($attendance));

        return back()->with('status', __('ปฏิเสธการเช็คชื่อสำเร็จ'));
    }

    public function exportExcel(Activity $activity)
    {
        $filename = 'attendees-'.str($activity->title)->slug().'.xlsx';

        return Excel::download(new ActivityAttendeesExport($activity), $filename);
    }

    public function exportMissingExcel(Activity $activity)
    {
        $filename = 'missing-'.str($activity->title)->slug().'.xlsx';

        return Excel::download(new ActivityMissingStudentsExport($activity), $filename);
    }
}

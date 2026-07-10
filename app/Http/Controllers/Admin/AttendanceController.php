<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ActivityAttendeesExport;
use App\Exports\ActivityMissingStudentsExport;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Faculty;
use App\Notifications\AttendanceApproved;
use App\Services\DynamicQrTokenGenerator;
use App\Services\SafeNotifier;
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

<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ActivityAttendeesExport;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\DynamicQrTokenGenerator;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AttendanceController extends Controller
{
    public function qrDisplay(Activity $activity)
    {
        return view('admin.attendance.qr-display', compact('activity'));
    }

    public function qrFragment(Activity $activity, DynamicQrTokenGenerator $qrTokens)
    {
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
    public function index(Activity $activity)
    {
        $attendances = $activity->attendances()
            ->with(['user.faculty', 'user.major'])
            ->orderByDesc('checkin_time')
            ->get();

        return view('admin.attendance.index', compact('activity', 'attendances'));
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

        $count = $activity->attendances()
            ->whereIn('id', $validated['attendance_ids'])
            ->update([
                'status' => 'auto_approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

        return back()->with('status', "อัปเดตสถานะสำเร็จ {$count} รายการ");
    }

    public function exportExcel(Activity $activity)
    {
        $filename = 'attendees-'.str($activity->title)->slug().'.xlsx';

        return Excel::download(new ActivityAttendeesExport($activity), $filename);
    }
}

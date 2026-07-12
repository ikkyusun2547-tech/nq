<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ClearanceReportController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExternalApprovalController;
use App\Http\Controllers\Admin\CreditTransferApprovalController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\LateCheckInApprovalController;
use App\Http\Controllers\Admin\MajorController;
use App\Http\Controllers\Admin\ParticipationReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentImportController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileSetupController;
use App\Http\Controllers\Student\ActivityController as StudentActivityController;
use App\Http\Controllers\Student\CheckInController;
use App\Http\Controllers\Student\CreditTransferController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\ExternalActivityController;
use App\Http\Controllers\Student\HourRequestController;
use App\Http\Controllers\Student\LateCheckInController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\SelfCheckInController;
use App\Http\Controllers\Student\TranscriptController;
use App\Models\Faculty;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['th', 'en'], true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('locale.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::post('/logout', [GoogleAuthController::class, 'logout'])->middleware('auth')->name('logout');

if (app()->environment('local')) {
    Route::get('/_test-login/{user}', function (\App\Models\User $user) {
        auth()->login($user);

        return redirect($user->isAdmin() ? '/admin/dashboard' : '/dashboard');
    });

    Route::get('/_camera-test', function () {
        return response(<<<'HTML'
            <!doctype html>
            <html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Camera test</title>
            <style>body{font-family:sans-serif;padding:1.5rem;max-width:480px;margin:0 auto}
            button{display:block;width:100%;padding:0.9rem;margin:0.5rem 0;font-size:1rem;border-radius:10px;border:1px solid #ccc}
            video{width:100%;border-radius:10px;background:#000}
            pre{white-space:pre-wrap;background:#f4f4f4;padding:0.75rem;border-radius:8px;font-size:0.85rem}</style>
            </head><body>
            <h2>ทดสอบกล้อง (ไม่ผ่าน QR scan)</h2>
            <button onclick="test('user')">เปิดกล้องหน้า (facingMode: user)</button>
            <button onclick="test('environment')">เปิดกล้องหลัง (facingMode: environment)</button>
            <button onclick="test(null)">เปิดกล้องแบบไม่ระบุ (video: true)</button>
            <video id="v" autoplay playsinline muted></video>
            <pre id="log">ผลลัพธ์จะขึ้นตรงนี้...</pre>
            <script>
            const log = document.getElementById('log');
            const video = document.getElementById('v');
            let stream = null;
            async function test(facing) {
                if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
                log.textContent = 'กำลังขอสิทธิ์กล้อง (' + (facing ?? 'any') + ')...';
                const constraints = facing ? { video: { facingMode: facing }, audio: false } : { video: true, audio: false };
                try {
                    stream = await navigator.mediaDevices.getUserMedia(constraints);
                    video.srcObject = stream;
                    const track = stream.getVideoTracks()[0];
                    log.textContent = 'สำเร็จ!\nlabel: ' + track.label + '\nsettings: ' + JSON.stringify(track.getSettings(), null, 2);
                } catch (err) {
                    log.textContent = 'ล้มเหลว\nname: ' + err.name + '\nmessage: ' + err.message;
                }
            }
            log.textContent += '\n\nuserAgent: ' + navigator.userAgent;
            </script>
            </body></html>
            HTML, 200, ['Content-Type' => 'text/html']);
    });
}

Route::middleware(['auth', 'srru.email'])->group(function () {
    Route::get('/setup-profile', [ProfileSetupController::class, 'show'])->name('profile-setup.show');
    Route::post('/setup-profile', [ProfileSetupController::class, 'store'])->name('profile-setup.store');

    Route::get('/api/faculties/{faculty}/majors', function (Faculty $faculty) {
        return $faculty->majors()->orderBy('name_th')->get(['id', 'name_th', 'degree_abbr']);
    })->name('api.majors.by-faculty');

    Route::middleware('profile.completed')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');

        Route::get('/activities', [StudentActivityController::class, 'index'])->name('activities.index');

        Route::get('/checkin', [CheckInController::class, 'show'])->name('checkin.show');
        Route::post('/checkin', [CheckInController::class, 'store'])->name('checkin.store');

        Route::get('/activities/{activity}/self-checkin', [SelfCheckInController::class, 'show'])->name('self-checkin.show');
        Route::post('/activities/{activity}/self-checkin', [SelfCheckInController::class, 'store'])->name('self-checkin.store');

        Route::get('/activities/{activity}/late-checkin', [LateCheckInController::class, 'show'])->name('late-checkin.show');
        Route::post('/activities/{activity}/late-checkin', [LateCheckInController::class, 'store'])->name('late-checkin.store');

        Route::get('/hour-requests', [HourRequestController::class, 'index'])->name('hour-requests.index');

        // Kept so old bookmarks/links still land on the right tab of the merged page.
        Route::get('/external-activities', fn () => redirect()->route('hour-requests.index', ['tab' => 'external']))->name('external-activities.index');
        Route::post('/external-activities', [ExternalActivityController::class, 'store'])->name('external-activities.store');

        Route::get('/credit-transfers', fn () => redirect()->route('hour-requests.index', ['tab' => 'credit']))->name('credit-transfers.index');
        Route::post('/credit-transfers', [CreditTransferController::class, 'store'])->name('credit-transfers.store');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');

        Route::get('/transcript', [TranscriptController::class, 'download'])->name('transcript.download');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::delete('/notifications/destroy-all', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/import', [StudentImportController::class, 'create'])->name('students.import.create');
        Route::post('/students/import', [StudentImportController::class, 'store'])->name('students.import.store');
        Route::get('/students/import/template', [StudentImportController::class, 'template'])->name('students.import.template');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');

        Route::resource('activities', ActivityController::class)->except(['show']);

        Route::get('/activities/{activity}/qr-display', [AttendanceController::class, 'qrDisplay'])->name('attendance.qr-display');
        Route::get('/activities/{activity}/qr-fragment', [AttendanceController::class, 'qrFragment'])->name('attendance.qr-fragment');
        Route::get('/activities/{activity}/qr-print', [AttendanceController::class, 'qrPrint'])->name('attendance.qr-print');
        Route::get('/activities/{activity}/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/activities/{activity}/attendance/bulk-approve', [AttendanceController::class, 'bulkApprove'])->name('attendance.bulk-approve');
        Route::get('/activities/{activity}/attendance/export', [AttendanceController::class, 'exportExcel'])->name('attendance.export');
        Route::get('/activities/{activity}/attendance/missing-export', [AttendanceController::class, 'exportMissingExcel'])->name('attendance.missing-export');

        Route::get('/attendance/flagged', [AttendanceController::class, 'flaggedIndex'])->name('attendance.flagged');
        Route::post('/attendance/{attendance}/approve', [AttendanceController::class, 'approve'])->name('attendance.approve');
        Route::post('/attendance/{attendance}/reject', [AttendanceController::class, 'reject'])->name('attendance.reject');

        Route::get('/external-activities', [ExternalApprovalController::class, 'index'])->name('external-activities.index');
        Route::post('/external-activities/{externalActivityRequest}/approve', [ExternalApprovalController::class, 'approve'])->name('external-activities.approve');
        Route::post('/external-activities/{externalActivityRequest}/reject', [ExternalApprovalController::class, 'reject'])->name('external-activities.reject');

        Route::get('/credit-transfers', [CreditTransferApprovalController::class, 'index'])->name('credit-transfers.index');
        Route::post('/credit-transfers/{creditTransferRequest}/approve', [CreditTransferApprovalController::class, 'approve'])->name('credit-transfers.approve');
        Route::post('/credit-transfers/{creditTransferRequest}/reject', [CreditTransferApprovalController::class, 'reject'])->name('credit-transfers.reject');

        Route::get('/late-checkins', [LateCheckInApprovalController::class, 'index'])->name('late-checkins.index');
        Route::post('/late-checkins/{lateCheckInRequest}/approve', [LateCheckInApprovalController::class, 'approve'])->name('late-checkins.approve');
        Route::post('/late-checkins/{lateCheckInRequest}/reject', [LateCheckInApprovalController::class, 'reject'])->name('late-checkins.reject');

        Route::get('/reports', [ParticipationReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/clearance', [ClearanceReportController::class, 'exportPdf'])->name('reports.clearance');
        Route::get('/reports/faculty-participation', [ParticipationReportController::class, 'exportExcel'])->name('reports.faculty-participation');

        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

        Route::post('/activities/{activity}/duplicate', [ActivityController::class, 'duplicate'])->name('activities.duplicate');

        Route::get('/announcements', [AnnouncementController::class, 'create'])->name('announcements.create');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');

        Route::middleware('super_admin')->group(function () {
            Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
            Route::post('/users/{user}/promote', [UserManagementController::class, 'promote'])->name('users.promote');
            Route::post('/users/{user}/demote', [UserManagementController::class, 'demote'])->name('users.demote');
            Route::post('/users/{user}/ban', [UserManagementController::class, 'ban'])->name('users.ban');
            Route::post('/users/{user}/unban', [UserManagementController::class, 'unban'])->name('users.unban');

            Route::resource('faculties', FacultyController::class)->except(['show']);
            Route::post('/faculties/{faculty}/majors', [MajorController::class, 'store'])->name('majors.store');
            Route::put('/majors/{major}', [MajorController::class, 'update'])->name('majors.update');
            Route::delete('/majors/{major}', [MajorController::class, 'destroy'])->name('majors.destroy');

            Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        });
    });
});

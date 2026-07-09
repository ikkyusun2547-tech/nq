<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ClearanceReportController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExternalApprovalController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileSetupController;
use App\Http\Controllers\Student\ActivityController as StudentActivityController;
use App\Http\Controllers\Student\CheckInController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\ExternalActivityController;
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

        Route::get('/external-activities', [ExternalActivityController::class, 'index'])->name('external-activities.index');
        Route::post('/external-activities', [ExternalActivityController::class, 'store'])->name('external-activities.store');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');

        Route::resource('activities', ActivityController::class)->except(['show']);

        Route::get('/activities/{activity}/qr-display', [AttendanceController::class, 'qrDisplay'])->name('attendance.qr-display');
        Route::get('/activities/{activity}/qr-fragment', [AttendanceController::class, 'qrFragment'])->name('attendance.qr-fragment');
        Route::get('/activities/{activity}/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/activities/{activity}/attendance/bulk-approve', [AttendanceController::class, 'bulkApprove'])->name('attendance.bulk-approve');
        Route::get('/activities/{activity}/attendance/export', [AttendanceController::class, 'exportExcel'])->name('attendance.export');
        Route::get('/activities/{activity}/attendance/missing-export', [AttendanceController::class, 'exportMissingExcel'])->name('attendance.missing-export');

        Route::get('/external-activities', [ExternalApprovalController::class, 'index'])->name('external-activities.index');
        Route::post('/external-activities/{externalActivityRequest}/approve', [ExternalApprovalController::class, 'approve'])->name('external-activities.approve');
        Route::post('/external-activities/{externalActivityRequest}/reject', [ExternalApprovalController::class, 'reject'])->name('external-activities.reject');

        Route::get('/reports/clearance', [ClearanceReportController::class, 'exportPdf'])->name('reports.clearance');
    });
});

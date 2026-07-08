<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ClearanceReportController;
use App\Http\Controllers\Admin\ExternalApprovalController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ProfileSetupController;
use App\Http\Controllers\Student\CheckInController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\ExternalActivityController;
use App\Models\Faculty;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

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
}

Route::middleware(['auth', 'srru.email'])->group(function () {
    Route::get('/setup-profile', [ProfileSetupController::class, 'show'])->name('profile-setup.show');
    Route::post('/setup-profile', [ProfileSetupController::class, 'store'])->name('profile-setup.store');

    Route::get('/api/faculties/{faculty}/majors', function (Faculty $faculty) {
        return $faculty->majors()->orderBy('name_th')->get(['id', 'name_th', 'degree_abbr']);
    })->name('api.majors.by-faculty');

    Route::middleware('profile.completed')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');

        Route::get('/checkin', [CheckInController::class, 'show'])->name('checkin.show');
        Route::post('/checkin', [CheckInController::class, 'store'])->name('checkin.store');

        Route::get('/external-activities', [ExternalActivityController::class, 'index'])->name('external-activities.index');
        Route::post('/external-activities', [ExternalActivityController::class, 'store'])->name('external-activities.store');
    });

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        Route::resource('activities', ActivityController::class)->except(['show']);

        Route::get('/activities/{activity}/qr-display', [AttendanceController::class, 'qrDisplay'])->name('attendance.qr-display');
        Route::get('/activities/{activity}/qr-fragment', [AttendanceController::class, 'qrFragment'])->name('attendance.qr-fragment');
        Route::get('/activities/{activity}/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/activities/{activity}/attendance/bulk-approve', [AttendanceController::class, 'bulkApprove'])->name('attendance.bulk-approve');
        Route::get('/activities/{activity}/attendance/export', [AttendanceController::class, 'exportExcel'])->name('attendance.export');

        Route::get('/external-activities', [ExternalApprovalController::class, 'index'])->name('external-activities.index');
        Route::post('/external-activities/{externalActivityRequest}/approve', [ExternalApprovalController::class, 'approve'])->name('external-activities.approve');
        Route::post('/external-activities/{externalActivityRequest}/reject', [ExternalApprovalController::class, 'reject'])->name('external-activities.reject');

        Route::get('/reports/clearance', [ClearanceReportController::class, 'exportPdf'])->name('reports.clearance');
    });
});

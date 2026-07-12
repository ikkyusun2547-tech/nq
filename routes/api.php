<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\FacultyController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileSetupController;
use App\Http\Controllers\Api\Student\ActivityController;
use App\Http\Controllers\Api\Student\CheckInController;
use App\Http\Controllers\Api\Student\CreditTransferController;
use App\Http\Controllers\Api\Student\DashboardController;
use App\Http\Controllers\Api\Student\ExternalActivityController;
use App\Http\Controllers\Api\Student\LateCheckInController;
use App\Http\Controllers\Api\Student\SelfCheckInController;
use App\Http\Controllers\Api\Student\TranscriptController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json(['ok' => true]));

Route::post('/auth/google', [AuthController::class, 'loginWithGoogle'])
    ->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::post('/device-token', [DeviceTokenController::class, 'store']);
    Route::delete('/device-token', [DeviceTokenController::class, 'destroy']);

    Route::middleware('api.srru.email')->group(function () {
        Route::get('/setup-profile', [ProfileSetupController::class, 'show']);
        Route::post('/setup-profile', [ProfileSetupController::class, 'store']);
        // Named distinctly from routes/web.php's existing (session-auth)
        // '/api/faculties/{faculty}/majors' closure — an identical
        // method+URI here would silently clobber that registration in
        // Laravel's route collection rather than raise an error.
        Route::get('/faculty-majors/{faculty}', [FacultyController::class, 'majors']);

        Route::middleware('api.profile.completed')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'show']);
            Route::get('/activities', [ActivityController::class, 'index']);
            Route::post('/checkin', [CheckInController::class, 'store']);

            Route::post('/activities/{activity}/self-checkin', [SelfCheckInController::class, 'store']);

            Route::get('/activities/{activity}/late-checkin', [LateCheckInController::class, 'show']);
            Route::post('/activities/{activity}/late-checkin', [LateCheckInController::class, 'store']);

            Route::get('/external-activities', [ExternalActivityController::class, 'index']);
            Route::post('/external-activities', [ExternalActivityController::class, 'store']);

            Route::get('/credit-transfers/positions', [CreditTransferController::class, 'positions']);
            Route::get('/credit-transfers', [CreditTransferController::class, 'index']);
            Route::post('/credit-transfers', [CreditTransferController::class, 'store']);

            Route::get('/transcript', [TranscriptController::class, 'download']);

            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
            Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
            Route::delete('/notifications/destroy-all', [NotificationController::class, 'destroyAll']);
            Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
        });
    });
});

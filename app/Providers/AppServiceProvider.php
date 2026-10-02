<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\CreditTransferRequest;
use App\Models\ExternalActivityRequest;
use App\Services\ClearanceWatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Anything that can add hours may push a student over the graduation
        // criteria — tell them once when it does (ClearanceWatcher). Bulk
        // query updates skip model events, so Admin\AttendanceController's
        // bulk approve calls the watcher itself.
        $this->watchClearance(Attendance::class, 'auto_approved');
        $this->watchClearance(ExternalActivityRequest::class, 'approved');
        $this->watchClearance(CreditTransferRequest::class, 'approved');
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function watchClearance(string $model, string $countsWhen): void
    {
        $model::saved(function (Model $record) use ($countsWhen) {
            if ($record->status !== $countsWhen || ! ($record->wasRecentlyCreated || $record->wasChanged('status'))) {
                return;
            }

            try {
                app(ClearanceWatcher::class)->check($record->user);
            } catch (Throwable $e) {
                // A congratulation must never undo the approval that triggered it.
                report($e);
            }
        });
    }
}

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requires the standard Laravel scheduler cron entry on the server
// (`* * * * * php artisan schedule:run`) — without that running, this never
// fires on its own. Hourly is frequent enough that a missed check-in never
// waits much more than an hour to see it was flagged as missed.
Schedule::command('app:close-ended-activities')->hourly();

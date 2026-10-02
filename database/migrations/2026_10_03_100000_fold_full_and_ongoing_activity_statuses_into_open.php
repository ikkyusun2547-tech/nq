<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Admins now pick from four statuses only (draft / open / closed /
 * cancelled — see Activity::SETTABLE_STATUSES). "ongoing" is derived from
 * the start/end time for display, and "full" is dropped (no sign-up to
 * fill; it also blocked check-in). Existing rows with either become 'open',
 * which is how they already behaved for students. Nothing else changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('activities')->whereIn('status', ['full', 'ongoing'])->update(['status' => 'open']);
    }

    public function down(): void
    {
        // Not reversible: which 'open' rows used to be full/ongoing isn't recorded, and both behave as open anyway.
    }
};

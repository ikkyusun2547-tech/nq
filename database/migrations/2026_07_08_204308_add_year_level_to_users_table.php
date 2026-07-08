<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('year_level')->nullable()->after('enrollment_year');
        });

        // One-time backfill for users who already completed their profile
        // under the old enrollment_year-derived calculation, so their
        // dashboard doesn't suddenly show a blank year level.
        $now = now();
        $currentBuddhistYear = $now->year + 543;
        $academicYear = $now->month >= 6 ? $currentBuddhistYear : $currentBuddhistYear - 1;

        DB::table('users')->whereNotNull('enrollment_year')->get(['id', 'enrollment_year'])->each(function ($user) use ($academicYear) {
            $yearLevel = max(1, min(4, ($academicYear - $user->enrollment_year) + 1));
            DB::table('users')->where('id', $user->id)->update(['year_level' => $yearLevel]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('year_level');
        });
    }
};

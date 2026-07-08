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
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedSmallInteger('academic_year')->nullable()->after('activity_type');
            $table->enum('semester', ['1', '2', '3'])->nullable()->after('academic_year');
        });

        // Backfill existing activities from their start_at date so nothing
        // is left blank (Thai academic year: sem 1 Jun-Oct, sem 2 Nov-Mar, sem 3/summer Apr-May).
        DB::table('activities')->whereNotNull('start_at')->get(['id', 'start_at'])->each(function ($activity) {
            $date = \Carbon\Carbon::parse($activity->start_at);
            $buddhistYear = $date->year + 543;
            $month = $date->month;

            if ($month >= 6 && $month <= 10) {
                $academicYear = $buddhistYear;
                $semester = '1';
            } elseif ($month >= 11 || $month <= 3) {
                $academicYear = $month >= 11 ? $buddhistYear : $buddhistYear - 1;
                $semester = '2';
            } else {
                $academicYear = $buddhistYear - 1;
                $semester = '3';
            }

            DB::table('activities')->where('id', $activity->id)->update([
                'academic_year' => $academicYear,
                'semester' => $semester,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['academic_year', 'semester']);
        });
    }
};

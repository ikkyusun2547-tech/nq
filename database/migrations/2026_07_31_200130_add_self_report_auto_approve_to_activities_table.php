<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Self-report submissions have always required manual admin
            // review (created as 'flagged' unconditionally) — this lets an
            // admin opt a specific activity out of that and auto-approve
            // self-reports the same way a normal QR check-in with no
            // detected anomaly already is.
            $table->boolean('self_report_auto_approve')->default(false)->after('checkin_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('self_report_auto_approve');
        });
    }
};

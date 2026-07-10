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
        Schema::table('attendances', function (Blueprint $table) {
            // Per-attendance override of the activity's credit_hours. Null
            // (the normal case for every realtime/self_report check-in)
            // means "use activities.credit_hours" — only late-request
            // approvals ever set this, when an admin grants a different
            // amount than the activity's nominal hours.
            $table->unsignedSmallInteger('credited_hours')->nullable()->after('distance_meters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('credited_hours');
        });
    }
};

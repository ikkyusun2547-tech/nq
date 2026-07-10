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
        Schema::table('late_check_in_requests', function (Blueprint $table) {
            // Null = credited at the activity's normal credit_hours; set only
            // when the admin overrides it during approval.
            $table->unsignedSmallInteger('hours_approved')->nullable()->after('reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('late_check_in_requests', function (Blueprint $table) {
            $table->dropColumn('hours_approved');
        });
    }
};

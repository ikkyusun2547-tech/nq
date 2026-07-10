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
            // 'late_request' = attendance created by an admin-approved
            // LateCheckInRequest, for a closed activity the student missed
            // checking in for.
            $table->enum('checkin_method', ['realtime', 'self_report', 'late_request'])->default('realtime')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->enum('checkin_method', ['realtime', 'self_report'])->default('realtime')->change();
        });
    }
};

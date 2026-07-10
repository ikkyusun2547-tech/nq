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
            $table->enum('checkin_method', ['realtime', 'self_report'])->default('realtime')->after('activity_id');
        });

        // Self-report check-ins have no live GPS fix or device fingerprint —
        // only the realtime (QR+GPS+selfie) method captures those.
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('student_lat', 10, 8)->nullable()->change();
            $table->decimal('student_lng', 11, 8)->nullable()->change();
            $table->string('device_uuid')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('student_lat', 10, 8)->nullable(false)->change();
            $table->decimal('student_lng', 11, 8)->nullable(false)->change();
            $table->string('device_uuid')->nullable(false)->change();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('checkin_method');
        });
    }
};

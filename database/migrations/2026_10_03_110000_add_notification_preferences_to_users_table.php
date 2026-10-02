<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - notification_preferences: what each user turned off on the
 *   "ตั้งค่าการแจ้งเตือน" page (null = all defaults, see
 *   App\Support\NotificationPreferences).
 * - cleared_notified_at: when the student was told they met the graduation
 *   activity criteria, so that congratulation goes out only once.
 * Both additive and nullable; existing rows are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable();
            $table->timestamp('cleared_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notification_preferences', 'cleared_notified_at']);
        });
    }
};

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
            $table->enum('checkin_method', ['realtime', 'self_report'])->default('realtime')->after('qr_secret');
            $table->dateTime('checkin_opens_at')->nullable()->after('checkin_method');
            $table->dateTime('checkin_closes_at')->nullable()->after('checkin_opens_at');
        });

        // GPS/QR fields only apply to the realtime (QR+GPS+selfie) method —
        // self_report activities may have no fixed GPS point at all.
        Schema::table('activities', function (Blueprint $table) {
            $table->decimal('location_lat', 10, 8)->nullable()->change();
            $table->decimal('location_lng', 11, 8)->nullable()->change();
            $table->unsignedInteger('allowed_radius')->nullable()->default(100)->change();
            $table->string('qr_secret')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->decimal('location_lat', 10, 8)->nullable(false)->change();
            $table->decimal('location_lng', 11, 8)->nullable(false)->change();
            $table->unsignedInteger('allowed_radius')->nullable(false)->default(100)->change();
            $table->string('qr_secret')->nullable(false)->change();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['checkin_method', 'checkin_opens_at', 'checkin_closes_at']);
        });
    }
};

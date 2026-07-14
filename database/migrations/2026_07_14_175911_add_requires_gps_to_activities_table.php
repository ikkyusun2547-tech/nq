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
            // Only meaningful when checkin_method is 'realtime' — lets an
            // admin drop the GPS-radius check for venues where GPS is
            // unreliable (indoors, underground) while keeping QR + selfie.
            $table->boolean('requires_gps')->default(true)->after('checkin_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('requires_gps');
        });
    }
};

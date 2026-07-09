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
        Schema::table('external_activity_requests', function (Blueprint $table) {
            // Null means "credit the hours as requested" — only set when an
            // admin overrides the amount, so most rows never need touching.
            $table->unsignedSmallInteger('hours_approved')->nullable()->after('hours_requested');
            $table->text('admin_comment')->nullable()->after('reject_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_activity_requests', function (Blueprint $table) {
            $table->dropColumn(['hours_approved', 'admin_comment']);
        });
    }
};

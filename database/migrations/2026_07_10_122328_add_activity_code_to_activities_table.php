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
            // Format: SRRU + 2-digit academic year + 1-digit category + 3-digit
            // sequence (resets every academic year). activity_seq is the raw
            // running number, kept separate so the next value can be computed
            // with MAX() instead of parsing the formatted string.
            $table->string('activity_code', 20)->nullable()->unique()->after('id');
            $table->unsignedInteger('activity_seq')->nullable()->after('activity_code');
            $table->index(['academic_year', 'activity_seq']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['academic_year', 'activity_seq']);
            $table->dropColumn(['activity_code', 'activity_seq']);
        });
    }
};

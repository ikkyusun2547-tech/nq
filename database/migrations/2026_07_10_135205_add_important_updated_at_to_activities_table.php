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
            // Set only when an edit touches a field students actually need to
            // know about (time/location/check-in method) — not on every save
            // — so the "อัปเดตแล้ว" badge doesn't fire on trivial edits like a
            // typo fix in the description.
            $table->dateTime('important_updated_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('important_updated_at');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which programme an activity is for: 'normal' (ภาคปกติ), 'special'
     * (ภาคพิเศษ กศ.บป.) or null for both — independent of the
     * faculty/major/year restrictions.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('target_program', 10)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('target_program');
        });
    }
};

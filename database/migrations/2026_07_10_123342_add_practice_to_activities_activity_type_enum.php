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
            // 'practice' = กิจกรรมซ้อม/เตรียมงาน: credits hours like 'elective'
            // but is excluded from the 25-activity graduation count (see
            // ActivityEvaluationService) since it isn't a real university
            // activity yet.
            $table->enum('activity_type', ['core', 'elective', 'practice'])->default('elective')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->enum('activity_type', ['core', 'elective'])->default('elective')->change();
        });
    }
};

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
        Schema::create('graduation_criteria', function (Blueprint $table) {
            $table->id();
            // Cohort (enrollment year), not "current" academic year — a
            // student keeps the criteria published for the year they
            // enrolled for their whole time here, even if the university
            // later changes the requirement for newer cohorts. Matches
            // users.enrollment_year, which is already Buddhist-era (e.g. 2565).
            $table->unsignedSmallInteger('enrollment_year');
            $table->enum('program_type', ['normal', 'special']);
            $table->unsignedSmallInteger('required_activities');
            $table->unsignedSmallInteger('required_hours');
            // {1: hours, 2: hours, 3: hours, 4: hours} — same shape as
            // ActivityEvaluationService::DEFAULT_CRITERIA's yearly_targets.
            $table->json('yearly_targets');
            $table->timestamps();

            $table->unique(['enrollment_year', 'program_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('graduation_criteria');
    }
};

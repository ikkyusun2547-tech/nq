<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The post-activity satisfaction survey was removed. Its create migration
 * (2026_10_01_120000) already ran on production, so its tables are dropped
 * here; dropIfExists keeps this a no-op on fresh installs that never had them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('activity_survey_answers');
        Schema::dropIfExists('activity_survey_responses');
        Schema::dropIfExists('activity_survey_submissions');
        Schema::dropIfExists('survey_questions');
    }

    public function down(): void
    {
        // Not recreated — the feature is gone.
    }
};

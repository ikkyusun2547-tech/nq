<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Post-activity satisfaction survey. "Who has answered" (submissions) is
 * deliberately kept apart from "what was answered" (responses/answers) with
 * no column linking the two, so results can't be traced back to a student.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->string('text');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('activity_survey_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['activity_id', 'user_id']);
        });

        Schema::create('activity_survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('activity_survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('activity_survey_responses')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('survey_questions')->restrictOnDelete();
            $table->unsignedTinyInteger('score');
            $table->unique(['response_id', 'question_id']);
        });

        $questions = [
            'ความเหมาะสมของวัน เวลา และสถานที่จัดกิจกรรม',
            'ความรู้หรือประโยชน์ที่ได้รับจากกิจกรรม',
            'สามารถนำสิ่งที่ได้รับไปใช้ในชีวิตประจำวันหรือการเรียนได้',
            'ความเหมาะสมของการจัดกิจกรรมและผู้จัด',
            'ความพึงพอใจต่อกิจกรรมโดยรวม',
        ];

        foreach ($questions as $index => $text) {
            DB::table('survey_questions')->insert([
                'text' => $text,
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_survey_answers');
        Schema::dropIfExists('activity_survey_responses');
        Schema::dropIfExists('activity_survey_submissions');
        Schema::dropIfExists('survey_questions');
    }
};

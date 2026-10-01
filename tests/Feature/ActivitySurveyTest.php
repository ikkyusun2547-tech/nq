<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Faculty;
use App\Models\SurveyQuestion;
use App\Models\User;
use App\Notifications\ActivitySurveyRequested;
use App\Services\ActivitySurvey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActivitySurveyTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::factory()->create([
            'role' => 'student',
            'email' => 'stu'.uniqid().'@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => (string) random_int(10000000000, 99999999999),
            'year_level' => 2,
            'program_type' => 'normal',
        ]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'email' => 'super@srru.ac.th']);
    }

    private function endedActivityAttendedBy(User ...$students): Activity
    {
        $activity = Activity::factory()->closed()->create([
            'start_at' => now()->subDays(2),
            'end_at' => now()->subDay(),
        ]);

        foreach ($students as $student) {
            Attendance::factory()->create(['user_id' => $student->id, 'activity_id' => $activity->id, 'status' => 'auto_approved']);
        }

        return $activity;
    }

    /** @return array<int, int> question id => score */
    private function allScores(int $score): array
    {
        return SurveyQuestion::active()->pluck('id')->mapWithKeys(fn ($id) => [$id => $score])->all();
    }

    public function test_the_migration_seeds_five_default_questions(): void
    {
        $this->assertSame(5, SurveyQuestion::active()->count());
    }

    public function test_an_attendee_can_submit_once_and_the_answer_is_not_linked_to_them(): void
    {
        $student = $this->student();
        $activity = $this->endedActivityAttendedBy($student);

        $this->actingAs($student)->get(route('activity-survey.show', $activity))->assertOk();

        $this->actingAs($student)
            ->post(route('activity-survey.store', $activity), ['scores' => $this->allScores(4), 'comment' => 'ดีมาก'])
            ->assertRedirect(route('activities.show', $activity));

        $this->assertDatabaseHas('activity_survey_submissions', ['activity_id' => $activity->id, 'user_id' => $student->id]);
        $this->assertDatabaseHas('activity_survey_responses', ['activity_id' => $activity->id, 'comment' => 'ดีมาก']);
        $this->assertSame(5, DB::table('activity_survey_answers')->count());
        $this->assertNotContains('user_id', Schema::getColumnListing('activity_survey_responses'));

        $this->actingAs($student)
            ->post(route('activity-survey.store', $activity), ['scores' => $this->allScores(1)])
            ->assertSessionHasErrors('scores');
        $this->assertSame(1, DB::table('activity_survey_responses')->count());
    }

    public function test_every_question_must_be_scored_between_one_and_five(): void
    {
        $student = $this->student();
        $activity = $this->endedActivityAttendedBy($student);
        $scores = $this->allScores(3);
        array_pop($scores);

        $this->actingAs($student)->post(route('activity-survey.store', $activity), ['scores' => $scores])->assertSessionHasErrors();
        $this->actingAs($student)->post(route('activity-survey.store', $activity), ['scores' => $this->allScores(6)])->assertSessionHasErrors();
        $this->assertSame(0, DB::table('activity_survey_responses')->count());
    }

    public function test_non_attendees_and_rejected_attendees_cannot_answer(): void
    {
        $outsider = $this->student();
        $rejected = $this->student();
        $activity = $this->endedActivityAttendedBy();
        Attendance::factory()->create(['user_id' => $rejected->id, 'activity_id' => $activity->id, 'status' => 'rejected']);

        $this->actingAs($outsider)->get(route('activity-survey.show', $activity))->assertForbidden();
        $this->actingAs($rejected)->get(route('activity-survey.show', $activity))->assertForbidden();
        $this->actingAs($outsider)->post(route('activity-survey.store', $activity), ['scores' => $this->allScores(5)])->assertSessionHasErrors('scores');
    }

    public function test_it_cannot_be_answered_before_the_activity_ends(): void
    {
        $student = $this->student();
        $activity = Activity::factory()->create(['start_at' => now()->subHour(), 'end_at' => now()->addHour(), 'status' => 'ongoing']);
        Attendance::factory()->create(['user_id' => $student->id, 'activity_id' => $activity->id, 'status' => 'auto_approved']);

        $this->actingAs($student)->post(route('activity-survey.store', $activity), ['scores' => $this->allScores(5)])->assertSessionHasErrors('scores');
    }

    public function test_closing_an_activity_invites_attendees_who_have_not_answered(): void
    {
        Notification::fake();
        $attendee = $this->student();
        $absent = $this->student();
        $activity = Activity::factory()->create(['status' => 'ongoing']);
        Attendance::factory()->create(['user_id' => $attendee->id, 'activity_id' => $activity->id, 'status' => 'auto_approved']);

        $this->actingAs(User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']))
            ->patch(route('admin.activities.update-status', $activity), ['status' => 'closed']);

        Notification::assertSentTo($attendee, ActivitySurveyRequested::class);
        Notification::assertNotSentTo($absent, ActivitySurveyRequested::class);
    }

    public function test_the_dashboard_reminds_students_of_pending_surveys(): void
    {
        $student = $this->student();
        $activity = $this->endedActivityAttendedBy($student);

        $this->actingAs($student)->get(route('dashboard'))->assertSee($activity->title);
        $this->assertCount(1, app(ActivitySurvey::class)->pendingFor($student));
    }

    public function test_stats_use_sample_standard_deviation_and_likert_levels(): void
    {
        $stats = ActivitySurvey::stats(collect([5, 4, 4, 3]));

        $this->assertSame(4.0, $stats['mean']);
        $this->assertSame(0.82, $stats['sd']);
        $this->assertSame('มาก', $stats['level']);
        $this->assertSame('มากที่สุด', ActivitySurvey::interpret(4.51));
        $this->assertSame('มาก', ActivitySurvey::interpret(4.50));
        $this->assertSame('น้อยที่สุด', ActivitySurvey::interpret(1.2));
    }

    public function test_admins_see_anonymous_results_and_can_export(): void
    {
        $a = $this->student();
        $b = $this->student();
        $activity = $this->endedActivityAttendedBy($a, $b);
        $survey = app(ActivitySurvey::class);
        $survey->submit($a, $activity, $this->allScores(5), 'ชอบมาก');
        $survey->submit($b, $activity, $this->allScores(4), null);

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        $this->actingAs($admin)->get(route('admin.activities.survey-results', $activity))
            ->assertOk()
            ->assertSee('4.50')
            ->assertSee('ชอบมาก')
            ->assertDontSee($a->name)
            ->assertDontSee($b->name);

        $this->actingAs($admin)->get(route('admin.activities.survey-results.export', $activity))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports.survey', ['academic_year' => '']))->assertOk()->assertSee($activity->title);
    }

    public function test_super_admins_can_edit_but_not_add_or_delete_questions(): void
    {
        $admin = $this->superAdmin();
        $question = SurveyQuestion::first();

        $this->actingAs($admin)->get(route('admin.survey-questions.index'))->assertOk();

        $this->actingAs($admin)->put(route('admin.survey-questions.update', $question), [
            'text' => 'ถ้อยคำใหม่', 'sort_order' => 9, 'is_active' => '0',
        ])->assertSessionHas('status');

        $question->refresh();
        $this->assertSame('ถ้อยคำใหม่', $question->text);
        $this->assertSame(9, $question->sort_order);
        $this->assertFalse($question->is_active);

        $this->actingAs($admin)->post('/admin/survey-questions', ['text' => 'x'])->assertStatus(405);
        $this->actingAs($admin)->delete(route('admin.survey-questions.update', $question))->assertStatus(405);
        $this->assertSame(5, SurveyQuestion::count());
    }

    public function test_the_last_active_question_cannot_be_turned_off(): void
    {
        $admin = $this->superAdmin();
        SurveyQuestion::query()->where('id', '!=', SurveyQuestion::first()->id)->update(['is_active' => false]);
        $last = SurveyQuestion::active()->first();

        $this->actingAs($admin)->put(route('admin.survey-questions.update', $last), [
            'text' => $last->text, 'sort_order' => 1, 'is_active' => '0',
        ])->assertSessionHas('error');

        $this->assertTrue($last->fresh()->is_active);
    }

    public function test_regular_admins_cannot_manage_questions(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        $this->actingAs($admin)->get(route('admin.survey-questions.index'))->assertForbidden();
    }
}

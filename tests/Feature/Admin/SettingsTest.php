<?php

namespace Tests\Feature\Admin;

use App\Models\GraduationCriteria;
use App\Models\User;
use App\Services\ActivityEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'email' => 'super@srru.ac.th']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'normal' => [
                'required_activities' => 30,
                'required_hours' => 120,
                'yearly_targets' => [1 => 45, 2 => 35, 3 => 25, 4 => 15],
            ],
            'special' => [
                'required_activities' => 5,
                'required_hours' => 60,
                'yearly_targets' => [1 => 25, 2 => 20, 3 => 10, 4 => 5],
            ],
        ], $overrides);
    }

    // --- authorization ---

    public function test_a_plain_admin_cannot_view_the_settings_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        $this->actingAs($admin)->get(route('admin.settings.index'))->assertForbidden();
    }

    // --- ActivityEvaluationService::criteria() resolution ---

    public function test_it_falls_back_to_default_criteria_when_nothing_is_configured(): void
    {
        $criteria = app(ActivityEvaluationService::class)->criteria(2568, 'normal');

        $this->assertSame(ActivityEvaluationService::DEFAULT_CRITERIA['normal']['required_hours'], $criteria['required_hours']);
    }

    public function test_it_uses_the_exact_cohort_year_when_configured(): void
    {
        GraduationCriteria::create([
            'enrollment_year' => 2568, 'program_type' => 'normal',
            'required_activities' => 30, 'required_hours' => 120,
            'yearly_targets' => [1 => 45, 2 => 35, 3 => 25, 4 => 15],
        ]);

        $criteria = app(ActivityEvaluationService::class)->criteria(2568, 'normal');

        $this->assertSame(120, $criteria['required_hours']);
    }

    public function test_it_falls_back_to_the_nearest_earlier_configured_cohort(): void
    {
        GraduationCriteria::create([
            'enrollment_year' => 2566, 'program_type' => 'normal',
            'required_activities' => 20, 'required_hours' => 90,
            'yearly_targets' => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        ]);

        // 2569 has no row of its own, but 2566 was published before it and is the closest.
        $criteria = app(ActivityEvaluationService::class)->criteria(2569, 'normal');

        $this->assertSame(90, $criteria['required_hours']);
    }

    public function test_it_does_not_fall_forward_to_a_later_cohort(): void
    {
        GraduationCriteria::create([
            'enrollment_year' => 2570, 'program_type' => 'normal',
            'required_activities' => 20, 'required_hours' => 90,
            'yearly_targets' => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        ]);

        // 2565 enrolled before 2570's criteria even existed — must not use it.
        $criteria = app(ActivityEvaluationService::class)->criteria(2565, 'normal');

        $this->assertSame(ActivityEvaluationService::DEFAULT_CRITERIA['normal']['required_hours'], $criteria['required_hours']);
    }

    public function test_a_students_summary_uses_their_own_cohorts_criteria(): void
    {
        GraduationCriteria::create([
            'enrollment_year' => 2568, 'program_type' => 'special',
            'required_activities' => 5, 'required_hours' => 60,
            'yearly_targets' => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        ]);

        $student = User::factory()->create([
            'role' => 'student', 'email' => 'stu@srru.ac.th',
            'enrollment_year' => 2568, 'program_type' => 'special',
        ]);

        $summary = app(ActivityEvaluationService::class)->summarize($student);

        $this->assertSame(60, $summary['required_hours']);
        $this->assertSame(5, $summary['required_activities']);
    }

    // --- controller ---

    public function test_it_creates_a_new_cohorts_criteria(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->post(route('admin.settings.store'), array_merge(['enrollment_year' => 2568], $this->payload()))
            ->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('graduation_criteria', [
            'enrollment_year' => 2568, 'program_type' => 'normal', 'required_hours' => 120,
        ]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $superAdmin->id, 'action' => 'created', 'type_label' => 'เกณฑ์การจบการศึกษา']);
    }

    public function test_it_rejects_a_duplicate_enrollment_year(): void
    {
        GraduationCriteria::create([
            'enrollment_year' => 2568, 'program_type' => 'normal',
            'required_activities' => 25, 'required_hours' => 100,
            'yearly_targets' => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.settings.store'), array_merge(['enrollment_year' => 2568], $this->payload()))
            ->assertSessionHasErrors('enrollment_year');
    }

    public function test_it_validates_criteria_input_on_store(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.settings.store'), array_merge(
                ['enrollment_year' => 2568],
                $this->payload(['normal' => ['required_activities' => 0, 'required_hours' => 100, 'yearly_targets' => [1 => 1, 2 => 1, 3 => 1, 4 => 1]]])
            ))
            ->assertSessionHasErrors('normal.required_activities');
    }

    public function test_it_updates_an_existing_cohorts_criteria(): void
    {
        GraduationCriteria::create([
            'enrollment_year' => 2568, 'program_type' => 'normal',
            'required_activities' => 25, 'required_hours' => 100,
            'yearly_targets' => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        ]);
        GraduationCriteria::create([
            'enrollment_year' => 2568, 'program_type' => 'special',
            'required_activities' => 4, 'required_hours' => 50,
            'yearly_targets' => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        ]);
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->put(route('admin.settings.update', 2568), $this->payload())
            ->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('graduation_criteria', [
            'enrollment_year' => 2568, 'program_type' => 'normal', 'required_hours' => 120,
        ]);
    }

    public function test_updating_a_year_that_was_never_created_is_a_404(): void
    {
        $this->actingAs($this->superAdmin())
            ->put(route('admin.settings.update', 2568), $this->payload())
            ->assertNotFound();
    }

    public function test_it_deletes_a_cohorts_criteria(): void
    {
        GraduationCriteria::create([
            'enrollment_year' => 2568, 'program_type' => 'normal',
            'required_activities' => 25, 'required_hours' => 100,
            'yearly_targets' => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        ]);
        GraduationCriteria::create([
            'enrollment_year' => 2568, 'program_type' => 'special',
            'required_activities' => 4, 'required_hours' => 50,
            'yearly_targets' => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        ]);
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->delete(route('admin.settings.destroy', 2568))->assertRedirect();

        $this->assertDatabaseMissing('graduation_criteria', ['enrollment_year' => 2568]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $superAdmin->id, 'action' => 'deleted', 'type_label' => 'เกณฑ์การจบการศึกษา']);
    }
}

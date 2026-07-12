<?php

namespace Tests\Feature\Admin;

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

    public function test_a_plain_admin_cannot_change_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_evaluation_service_falls_back_to_the_default_criteria(): void
    {
        $criteria = app(ActivityEvaluationService::class)->criteria();

        $this->assertSame(
            ActivityEvaluationService::DEFAULT_CRITERIA['normal']['required_hours'],
            $criteria['normal']['required_hours'],
        );
    }

    public function test_it_saves_new_criteria_and_the_evaluation_service_picks_it_up(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->put(route('admin.settings.update'), [
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
        ])->assertRedirect();

        $criteria = app(ActivityEvaluationService::class)->criteria();

        $this->assertSame(30, $criteria['normal']['required_activities']);
        $this->assertSame(120, $criteria['normal']['required_hours']);
        $this->assertSame(45, $criteria['normal']['yearly_targets'][1]);
        $this->assertSame(60, $criteria['special']['required_hours']);
    }

    public function test_it_validates_criteria_input(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->put(route('admin.settings.update'), [
            'normal' => ['required_activities' => 0, 'required_hours' => 100, 'yearly_targets' => [1 => 1, 2 => 1, 3 => 1, 4 => 1]],
            'special' => ['required_activities' => 4, 'required_hours' => 50, 'yearly_targets' => [1 => 1, 2 => 1, 3 => 1, 4 => 1]],
        ])->assertSessionHasErrors('normal.required_activities');
    }
}

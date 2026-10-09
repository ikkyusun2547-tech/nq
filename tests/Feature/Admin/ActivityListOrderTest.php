<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AcademicYearCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityListOrderTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $title, string $status, $start, array $extra = []): Activity
    {
        return Activity::factory()->create(array_merge([
            'title' => $title,
            'status' => $status,
            'start_at' => $start,
            'end_at' => (clone $start)->addHours(2),
            'academic_year' => AcademicYearCalculator::forDate(now()),
        ], $extra));
    }

    public function test_the_default_order_groups_by_when_with_review_first(): void
    {
        $this->travelTo(now()->setTime(10, 0));

        $this->make('Cancelled', 'cancelled', now()->addDays(1));
        $this->make('Ended old', 'closed', now()->subDays(20));
        $this->make('Ended recent', 'closed', now()->subDays(2));
        $this->make('Draft', 'draft', now()->addDays(10));
        $this->make('Upcoming far', 'open', now()->addDays(30));
        $this->make('Upcoming soon', 'open', now()->addDays(3));
        $this->make('Live now', 'open', now()->subHour());
        $needsReview = $this->make('Needs review', 'closed', now()->subDays(40));
        Attendance::factory()->for($needsReview)->flagged()->create();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $response = $this->actingAs($admin)->get(route('admin.activities.index'))->assertOk();

        $this->assertSame(
            ['Needs review', 'Live now', 'Upcoming soon', 'Draft', 'Upcoming far', 'Ended recent', 'Ended old', 'Cancelled'],
            $response->viewData('activities')->pluck('title')->all()
        );
        $response->assertSeeInOrder(['ต้องตรวจสอบ', 'วันนี้', '7 วันข้างหน้า', 'ถัดไป', 'ผ่านไปแล้ว', 'ถูกยกเลิก']);
    }

    public function test_drafts_sit_on_their_own_date_and_are_flagged(): void
    {
        $this->travelTo(now()->setTime(8, 0));

        $this->make('Next month', 'open', now()->addDays(30));
        $this->make('Draft today', 'draft', now()->addHours(2));
        $this->make('Draft past', 'draft', now()->subDays(5));

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $response = $this->actingAs($admin)->get(route('admin.activities.index'))->assertOk();

        $this->assertSame(['Draft today', 'Next month', 'Draft past'], $response->viewData('activities')->pluck('title')->all());
        $response->assertSeeInOrder(['วันนี้', 'Draft today', 'ยังไม่เผยแพร่', 'ถัดไป', 'Next month', 'ผ่านไปแล้ว', 'Draft past', 'ไม่ได้เผยแพร่']);
    }

    public function test_an_explicit_column_sort_still_wins_and_hides_the_group_headings(): void
    {
        $this->make('B', 'open', now()->addDays(2));
        $this->make('A', 'closed', now()->subDays(2));

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $response = $this->actingAs($admin)->get(route('admin.activities.index', ['sort' => 'title', 'dir' => 'asc']))->assertOk();

        $this->assertSame(['A', 'B'], $response->viewData('activities')->pluck('title')->all());
        $response->assertDontSee('ผ่านไปแล้ว');
    }
}

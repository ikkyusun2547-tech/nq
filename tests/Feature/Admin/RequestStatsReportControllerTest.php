<?php

namespace Tests\Feature\Admin;

use App\Models\ExternalActivityRequest;
use App\Models\User;
use App\Services\ActivityEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestStatsReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private function request(array $overrides = []): ExternalActivityRequest
    {
        return ExternalActivityRequest::create(array_merge([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'stu'.uniqid().'@srru.ac.th'])->id,
            'title' => 'ค่ายอาสาพัฒนาชุมชน',
            'organization' => 'มูลนิธิทดสอบ',
            'activity_date' => now()->subDays(3)->toDateString(),
            'activity_category' => 'volunteer',
            'hours_requested' => 10,
            'proof_image_path' => 'external-activities/fake.jpg',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_it_computes_the_approval_rate_from_decided_requests_only(): void
    {
        $this->request(['status' => 'approved', 'reviewed_at' => now()]);
        $this->request(['status' => 'approved', 'reviewed_at' => now()]);
        $this->request(['status' => 'rejected', 'reviewed_at' => now()]);
        $this->request(['status' => 'pending']); // excluded from the rate — not decided yet

        $stats = app(ActivityEvaluationService::class)->requestStats();

        $this->assertSame(4, $stats['external']['total']);
        $this->assertSame(1, $stats['external']['pending']);
        $this->assertSame(2, $stats['external']['approved']);
        $this->assertSame(1, $stats['external']['rejected']);
        $this->assertSame(66.7, $stats['external']['approval_rate']); // 2/3
    }

    public function test_approval_rate_is_null_when_nothing_has_been_decided_yet(): void
    {
        $this->request(['status' => 'pending']);

        $stats = app(ActivityEvaluationService::class)->requestStats();

        $this->assertNull($stats['external']['approval_rate']);
    }

    public function test_a_student_cannot_view_the_report(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.reports.request-stats'))->assertForbidden();
    }

    public function test_an_admin_can_view_the_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        $this->actingAs($admin)->get(route('admin.reports.request-stats'))->assertOk();
    }
}

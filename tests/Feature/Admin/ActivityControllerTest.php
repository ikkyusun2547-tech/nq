<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Faculty;
use App\Models\User;
use App\Notifications\ActivityCreated;
use App\Notifications\ActivityMissed;
use App\Notifications\ActivityUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'student', 'email' => 'stu'.uniqid().'@srru.ac.th']);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'กิจกรรมทดสอบ',
            'activity_level' => 'university',
            'activity_category' => 'academic',
            'activity_type' => 'elective',
            'academic_year' => 2568,
            'semester' => '1',
            'credit_hours' => 3,
            'start_at' => now()->addDay()->format('Y-m-d H:i'),
            'end_at' => now()->addDay()->addHours(2)->format('Y-m-d H:i'),
            'location_name' => 'หอประชุมใหญ่',
            'checkin_method' => 'realtime',
            'requires_gps' => false,
            'status' => 'open',
        ], $overrides);
    }

    // --- authorization ---

    public function test_a_student_cannot_create_an_activity(): void
    {
        $this->actingAs($this->student())
            ->post(route('admin.activities.store'), $this->validPayload())
            ->assertForbidden();
    }

    // --- store() ---

    public function test_it_creates_an_activity_and_generates_an_activity_code(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.activities.store'), $this->validPayload());

        $response->assertRedirect(route('admin.activities.index'));
        $activity = Activity::where('title', 'กิจกรรมทดสอบ')->firstOrFail();
        $this->assertNotNull($activity->activity_code);
        $this->assertSame($admin->id, $activity->created_by);
    }

    public function test_creating_an_open_activity_notifies_eligible_students(): void
    {
        Notification::fake();
        $student = $this->student();

        $this->actingAs($this->admin())->post(route('admin.activities.store'), $this->validPayload(['status' => 'open']));

        Notification::assertSentTo($student, ActivityCreated::class);
    }

    public function test_creating_a_draft_activity_does_not_notify_anyone(): void
    {
        Notification::fake();
        $this->student();

        $this->actingAs($this->admin())->post(route('admin.activities.store'), $this->validPayload(['status' => 'draft']));

        Notification::assertNothingSent();
    }

    public function test_a_core_activity_is_forced_to_five_credit_hours(): void
    {
        $this->actingAs($this->admin())->post(route('admin.activities.store'), $this->validPayload([
            'activity_type' => 'core',
            'credit_hours' => 1,
        ]));

        $this->assertDatabaseHas('activities', ['title' => 'กิจกรรมทดสอบ', 'credit_hours' => 5]);
    }

    public function test_it_restricts_the_activity_to_the_selected_faculty(): void
    {
        $faculty = Faculty::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.activities.store'), $this->validPayload([
            'faculty_ids' => [$faculty->id],
        ]));

        $activity = Activity::where('title', 'กิจกรรมทดสอบ')->firstOrFail();
        $this->assertDatabaseHas('activity_restrictions', [
            'activity_id' => $activity->id,
            'faculty_id' => $faculty->id,
        ]);
    }

    public function test_store_requires_end_at_to_be_after_start_at(): void
    {
        $this->actingAs($this->admin())->post(route('admin.activities.store'), $this->validPayload([
            'start_at' => now()->addDay()->format('Y-m-d H:i'),
            'end_at' => now()->format('Y-m-d H:i'),
        ]))->assertSessionHasErrors('end_at');
    }

    public function test_gps_fields_are_required_when_realtime_checkin_requires_gps(): void
    {
        $this->actingAs($this->admin())->post(route('admin.activities.store'), $this->validPayload([
            'requires_gps' => true,
        ]))->assertSessionHasErrors(['location_lat', 'location_lng', 'allowed_radius']);
    }

    // --- update() ---

    public function test_updating_the_location_bumps_important_updated_at_and_notifies_eligible_students(): void
    {
        Notification::fake();
        $activity = Activity::factory()->create(['location_name' => 'เดิม', 'important_updated_at' => null]);
        $student = $this->student();

        $this->actingAs($this->admin())->put(route('admin.activities.update', $activity), $this->validPayload([
            'location_name' => 'สถานที่ใหม่',
            'start_at' => $activity->start_at->format('Y-m-d H:i'),
            'end_at' => $activity->end_at->format('Y-m-d H:i'),
        ]));

        $activity->refresh();
        $this->assertSame('สถานที่ใหม่', $activity->location_name);
        $this->assertNotNull($activity->important_updated_at);
        Notification::assertSentTo($student, ActivityUpdated::class);
    }

    public function test_updating_an_insignificant_field_does_not_notify_anyone(): void
    {
        Notification::fake();
        $activity = Activity::factory()->noGpsRequired()->create(['title' => 'เดิม', 'important_updated_at' => null]);
        $this->student();

        $this->actingAs($this->admin())->put(route('admin.activities.update', $activity), $this->validPayload([
            'title' => 'ชื่อใหม่เฉยๆ',
            'location_name' => $activity->location_name,
            'start_at' => $activity->start_at->format('Y-m-d H:i'),
            'end_at' => $activity->end_at->format('Y-m-d H:i'),
            'requires_gps' => false,
        ]));

        $activity->refresh();
        $this->assertSame('ชื่อใหม่เฉยๆ', $activity->title);
        $this->assertNull($activity->important_updated_at);
        Notification::assertNothingSent();
    }

    public function test_closing_an_activity_notifies_students_who_never_checked_in(): void
    {
        Notification::fake();
        $activity = Activity::factory()->create(['status' => 'open']);
        $missingStudent = $this->student();
        $checkedInStudent = $this->student();
        Attendance::factory()->create(['activity_id' => $activity->id, 'user_id' => $checkedInStudent->id]);

        $this->actingAs($this->admin())->put(route('admin.activities.update', $activity), $this->validPayload([
            'status' => 'closed',
            'location_name' => $activity->location_name,
            'start_at' => $activity->start_at->format('Y-m-d H:i'),
            'end_at' => $activity->end_at->format('Y-m-d H:i'),
        ]));

        Notification::assertSentTo($missingStudent, ActivityMissed::class);
        Notification::assertNotSentTo($checkedInStudent, ActivityMissed::class);
    }

    // --- destroy() ---

    public function test_it_refuses_to_delete_an_activity_with_checked_in_students(): void
    {
        $activity = Activity::factory()->create();
        Attendance::factory()->create(['activity_id' => $activity->id]);

        $response = $this->actingAs($this->admin())->delete(route('admin.activities.destroy', $activity));

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('activities', ['id' => $activity->id]);
    }

    public function test_it_deletes_an_activity_with_no_attendances(): void
    {
        $activity = Activity::factory()->create();

        $response = $this->actingAs($this->admin())->delete(route('admin.activities.destroy', $activity));

        $response->assertRedirect()->assertSessionHas('status');
        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
    }
}

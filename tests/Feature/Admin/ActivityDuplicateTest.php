<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_cannot_duplicate_an_activity(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);
        $activity = Activity::factory()->create();

        $this->actingAs($student)->post(route('admin.activities.duplicate', $activity))->assertForbidden();
    }

    public function test_it_duplicates_an_activity_as_a_draft_with_dates_carried_over(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $original = Activity::factory()->create([
            'title' => 'ปฐมนิเทศนักศึกษาใหม่',
            'status' => 'open',
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(3)->addHours(2),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.activities.duplicate', $original));

        $copy = Activity::where('id', '!=', $original->id)->latest('id')->first();

        $response->assertRedirect(route('admin.activities.edit', $copy));
        $this->assertNotNull($copy);
        $this->assertStringContainsString($original->title, $copy->title);
        $this->assertSame('draft', $copy->status);
        $this->assertNotNull($copy->start_at);
        $this->assertEquals($original->start_at->format('Y-m-d H:i'), $copy->start_at->format('Y-m-d H:i'));
        $this->assertNotSame($original->activity_code, $copy->activity_code);
    }

    public function test_it_copies_restrictions_to_the_duplicate(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $faculty = \App\Models\Faculty::factory()->create();
        $original = Activity::factory()->create();
        $original->restrictions()->create(['faculty_id' => $faculty->id]);

        $this->actingAs($admin)->post(route('admin.activities.duplicate', $original));

        $copy = Activity::where('id', '!=', $original->id)->latest('id')->first();

        $this->assertSame(1, $copy->restrictions()->count());
        $this->assertSame($faculty->id, $copy->restrictions()->first()->faculty_id);
    }
}

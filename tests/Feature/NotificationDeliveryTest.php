<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use App\Notifications\ActivityCancelled;
use App\Notifications\ActivityCreated;
use App\Services\SafeNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The bell entry must not wait for the queue worker (on Railway that meant
 * minutes); only push / email go through the queue.
 */
class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'database']);
    }

    public function test_the_bell_entry_is_written_immediately_and_only_push_is_queued(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        SafeNotifier::send($student, new ActivityCreated(Activity::factory()->create()));

        $this->assertSame(1, $student->notifications()->count(), 'bell entry should exist right away');
        $this->assertSame(1, DB::table('jobs')->count(), 'only the push should be waiting in the queue');
    }

    public function test_notifications_that_email_queue_push_and_mail(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        SafeNotifier::send($student, new ActivityCancelled(Activity::factory()->create()));

        $this->assertSame(1, $student->notifications()->count());
        $this->assertSame(2, DB::table('jobs')->count());
    }
}

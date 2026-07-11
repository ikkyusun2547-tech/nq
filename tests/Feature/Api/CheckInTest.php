<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use App\Notifications\AttendanceFlagged;
use App\Services\DynamicQrTokenGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The 8-scenario manual test protocol from the implementation plan's Phase 6,
 * run here as automated assertions instead of a live device — this is the
 * highest-risk feature (QR rotation + HMAC + GPS geofence + device fraud
 * detection), all reused verbatim from the existing web CheckInController's
 * AttendanceAutomationService/DynamicQrTokenGenerator.
 */
class CheckInTest extends TestCase
{
    use RefreshDatabase;

    private DynamicQrTokenGenerator $tokens;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->tokens = app(DynamicQrTokenGenerator::class);
    }

    private function studentUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'student',
            'email' => 'student'.uniqid().'@srru.ac.th',
            'faculty_id' => \App\Models\Faculty::factory(),
            'student_id' => (string) fake()->unique()->numerify('###########'),
            'year_level' => 2,
            'program_type' => 'normal',
        ], $overrides));
    }

    private function activityAt(float $lat, float $lng, int $radius = 100): Activity
    {
        return Activity::factory()->create([
            'status' => 'open',
            'location_lat' => $lat,
            'location_lng' => $lng,
            'allowed_radius' => $radius,
        ]);
    }

    private function submit(User $user, Activity $activity, string $token, float $lat, float $lng, string $deviceUuid): \Illuminate\Testing\TestResponse
    {
        Sanctum::actingAs($user);

        return $this->postJson('/api/checkin', [
            'qr_token' => $token,
            'location_lat' => $lat,
            'location_lng' => $lng,
            'device_uuid' => $deviceUuid,
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
        ]);
    }

    // 1. Valid rotating token + in-radius GPS + selfie within window -> auto_approved
    public function test_valid_checkin_is_auto_approved(): void
    {
        $activity = $this->activityAt(14.0, 103.0);
        $user = $this->studentUser();
        $token = $this->tokens->generate($activity);

        $response = $this->submit($user, $activity, $token, 14.0, 103.0, 'device-1');

        $response->assertOk()->assertJson(['status' => 'auto_approved']);
        $this->assertDatabaseHas('attendances', ['user_id' => $user->id, 'activity_id' => $activity->id, 'status' => 'auto_approved']);
    }

    // 2. Same token reused after the ~2-minute grace window -> 422 expired
    public function test_expired_token_is_rejected(): void
    {
        $activity = $this->activityAt(14.0, 103.0);
        $user = $this->studentUser();
        $longExpiredWindow = $this->tokens->currentWindow() - 100;
        $token = $this->tokens->generate($activity, $longExpiredWindow);

        $response = $this->submit($user, $activity, $token, 14.0, 103.0, 'device-2');

        $response->assertStatus(422);
        $this->assertDatabaseMissing('attendances', ['user_id' => $user->id, 'activity_id' => $activity->id]);
    }

    // 3. Valid token, GPS outside allowed_radius -> flagged / GPS_OUT_OF_BOUNDS
    public function test_out_of_radius_checkin_is_flagged(): void
    {
        $activity = $this->activityAt(14.0, 103.0, radius: 100);
        $user = $this->studentUser();
        $token = $this->tokens->generate($activity);

        // ~1.1km away, well past a 100m radius.
        $response = $this->submit($user, $activity, $token, 14.01, 103.0, 'device-3');

        $response->assertOk()->assertJson(['status' => 'flagged']);
        $this->assertDatabaseHas('attendances', ['user_id' => $user->id, 'flag_reason' => 'GPS_OUT_OF_BOUNDS']);
    }

    // 4. Two users, same device_uuid, same activity -> second is flagged / DEVICE_SHARING_SUSPECTED
    public function test_shared_device_is_flagged_for_the_second_user(): void
    {
        $activity = $this->activityAt(14.0, 103.0);
        $userA = $this->studentUser();
        $userB = $this->studentUser();
        $sharedDevice = 'shared-device';

        $this->submit($userA, $activity, $this->tokens->generate($activity), 14.0, 103.0, $sharedDevice)
            ->assertOk()->assertJson(['status' => 'auto_approved']);

        $response = $this->submit($userB, $activity, $this->tokens->generate($activity), 14.0, 103.0, $sharedDevice);

        $response->assertOk()->assertJson(['status' => 'flagged']);
        $this->assertDatabaseHas('attendances', ['user_id' => $userB->id, 'flag_reason' => 'DEVICE_SHARING_SUSPECTED']);
    }

    // 5. Static/printable QR -> always flagged / PRINTED_QR_USED
    public function test_static_qr_never_auto_approves(): void
    {
        $activity = $this->activityAt(14.0, 103.0);
        $user = $this->studentUser();
        $token = $this->tokens->generateStatic($activity);

        $response = $this->submit($user, $activity, $token, 14.0, 103.0, 'device-5');

        $response->assertOk()->assertJson(['status' => 'flagged']);
        $this->assertDatabaseHas('attendances', ['user_id' => $user->id, 'flag_reason' => 'PRINTED_QR_USED']);
    }

    // 6. Duplicate check-in by the same user -> 422
    public function test_duplicate_checkin_is_rejected(): void
    {
        $activity = $this->activityAt(14.0, 103.0);
        $user = $this->studentUser();

        $this->submit($user, $activity, $this->tokens->generate($activity), 14.0, 103.0, 'device-6a')
            ->assertOk()->assertJson(['status' => 'auto_approved']);

        $response = $this->submit($user, $activity, $this->tokens->generate($activity), 14.0, 103.0, 'device-6b');

        $response->assertStatus(422);
        $this->assertSame(1, Attendance::where('user_id', $user->id)->where('activity_id', $activity->id)->count());
    }

    // 7. Admin gets notified when a check-in is flagged
    public function test_admin_is_notified_when_a_checkin_is_flagged(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $activity = $this->activityAt(14.0, 103.0);
        $user = $this->studentUser();

        $this->submit($user, $activity, $this->tokens->generateStatic($activity), 14.0, 103.0, 'device-7')
            ->assertOk()->assertJson(['status' => 'flagged']);

        Notification::assertSentTo($admin, AttendanceFlagged::class);
    }
}

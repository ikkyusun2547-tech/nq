<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use App\Notifications\ActivityCreated;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use RuntimeException;
use Tests\TestCase;

/**
 * Production ran the queue on a service without the Firebase key file, so
 * building the FCM client threw before FcmChannel's own try/catch and every
 * push job failed. A Firebase problem must only skip the push.
 */
class FcmChannelResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_firebase_setup_error_skips_the_push_instead_of_failing_the_job(): void
    {
        $this->app->bind(Messaging::class, fn () => throw new RuntimeException('service-account.json: No such file or directory'));
        Log::spy();

        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);
        $student->deviceTokens()->create(['token' => 'token-123', 'platform' => 'web']);

        app(FcmChannel::class)->send($student, new ActivityCreated(Activity::factory()->create()));

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_the_key_can_come_straight_from_the_json_variable(): void
    {
        $json = '{"type":"service_account","project_id":"demo"}';
        putenv("FIREBASE_CREDENTIALS_JSON=$json");
        $_ENV['FIREBASE_CREDENTIALS_JSON'] = $json;
        $_SERVER['FIREBASE_CREDENTIALS_JSON'] = $json;

        $config = require config_path('firebase.php');

        $this->assertSame($json, $config['projects']['app']['credentials']);

        putenv('FIREBASE_CREDENTIALS_JSON');
        unset($_ENV['FIREBASE_CREDENTIALS_JSON'], $_SERVER['FIREBASE_CREDENTIALS_JSON']);
    }
}

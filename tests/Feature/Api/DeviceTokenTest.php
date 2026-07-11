<?php

namespace Tests\Feature\Api;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_authentication(): void
    {
        $this->postJson('/api/device-token', ['token' => 'abc'])->assertUnauthorized();
    }

    public function test_it_registers_a_new_device_token(): void
    {
        $user = User::factory()->create(['role' => 'student', 'email' => 'student@srru.ac.th']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/device-token', ['token' => 'fcm-token-1']);

        $response->assertOk();
        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token-1',
            'platform' => 'android',
        ]);
    }

    public function test_it_reassigns_a_token_previously_owned_by_another_user(): void
    {
        $previousOwner = User::factory()->create(['role' => 'student', 'email' => 'old@srru.ac.th']);
        DeviceToken::create(['user_id' => $previousOwner->id, 'token' => 'shared-device', 'platform' => 'android']);

        $newOwner = User::factory()->create(['role' => 'student', 'email' => 'new@srru.ac.th']);
        Sanctum::actingAs($newOwner);

        $response = $this->postJson('/api/device-token', ['token' => 'shared-device']);

        $response->assertOk();
        $this->assertSame(1, DeviceToken::where('token', 'shared-device')->count());
        $this->assertDatabaseHas('device_tokens', ['token' => 'shared-device', 'user_id' => $newOwner->id]);
    }

    public function test_it_removes_a_device_token(): void
    {
        $user = User::factory()->create(['role' => 'student', 'email' => 'student@srru.ac.th']);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'fcm-token-1', 'platform' => 'android']);
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/device-token', ['token' => 'fcm-token-1']);

        $response->assertOk();
        $this->assertDatabaseMissing('device_tokens', ['token' => 'fcm-token-1']);
    }
}

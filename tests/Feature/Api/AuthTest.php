<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleToken(array $overrides = []): void
    {
        Http::fake([
            'oauth2.googleapis.com/tokeninfo*' => Http::response(array_merge([
                'aud' => config('services.google.client_id'),
                'sub' => 'google-user-123',
                'email' => 'somchai.s@srru.ac.th',
                'email_verified' => 'true',
                'name' => 'Somchai Sriwan',
                'picture' => 'https://example.com/avatar.jpg',
            ], $overrides)),
        ]);
    }

    public function test_login_with_valid_token_creates_user_and_issues_token(): void
    {
        $this->fakeGoogleToken();

        $response = $this->postJson('/api/auth/google', ['id_token' => 'fake-token']);

        $response->assertOk()->assertJsonStructure(['token', 'token_type', 'user', 'profile_completed', 'is_admin']);
        $this->assertFalse($response->json('profile_completed'));
        $this->assertDatabaseHas('users', ['email' => 'somchai.s@srru.ac.th', 'role' => 'student']);
    }

    public function test_login_rejects_non_srru_email_domain(): void
    {
        $this->fakeGoogleToken(['email' => 'someone@gmail.com']);

        $response = $this->postJson('/api/auth/google', ['id_token' => 'fake-token']);

        $response->assertStatus(403)->assertJson(['error_code' => 'EMAIL_DOMAIN_NOT_ALLOWED']);
        $this->assertDatabaseMissing('users', ['email' => 'someone@gmail.com']);
    }

    public function test_login_rejects_banned_account(): void
    {
        User::factory()->create(['email' => 'somchai.s@srru.ac.th', 'account_status' => 'banned']);
        $this->fakeGoogleToken();

        $response = $this->postJson('/api/auth/google', ['id_token' => 'fake-token']);

        $response->assertStatus(403)->assertJson(['error_code' => 'ACCOUNT_BANNED']);
    }

    public function test_login_rejects_unverified_email(): void
    {
        $this->fakeGoogleToken(['email_verified' => 'false']);

        $response = $this->postJson('/api/auth/google', ['id_token' => 'fake-token']);

        $response->assertStatus(401)->assertJson(['error_code' => 'INVALID_GOOGLE_TOKEN']);
    }

    public function test_login_rejects_wrong_audience(): void
    {
        $this->fakeGoogleToken(['aud' => 'some-other-client-id']);

        $response = $this->postJson('/api/auth/google', ['id_token' => 'fake-token']);

        $response->assertStatus(401)->assertJson(['error_code' => 'INVALID_GOOGLE_TOKEN']);
    }

    public function test_me_requires_a_valid_token(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertOk();

        // The guard caches its resolved user per-instance within a single test
        // process (Illuminate\Auth\RequestGuard::user()), so a second chained
        // request here would still pass even with the token deleted — a real
        // client always hits a fresh process, so we assert DB state directly.
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}

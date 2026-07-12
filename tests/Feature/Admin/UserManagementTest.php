<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'email' => 'super@srru.ac.th']);
    }

    public function test_a_plain_admin_cannot_access_user_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_a_super_admin_can_list_users(): void
    {
        $superAdmin = $this->superAdmin();
        User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th', 'name_thai' => 'นักศึกษาทดสอบ']);

        $response = $this->actingAs($superAdmin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('นักศึกษาทดสอบ');
    }

    public function test_it_promotes_a_student_to_admin(): void
    {
        $superAdmin = $this->superAdmin();
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($superAdmin)
            ->post(route('admin.users.promote', $student))
            ->assertRedirect();

        $this->assertSame('admin', $student->fresh()->role);
    }

    public function test_it_refuses_to_promote_someone_who_is_already_an_admin(): void
    {
        $superAdmin = $this->superAdmin();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'a2@srru.ac.th']);

        $this->actingAs($superAdmin)
            ->post(route('admin.users.promote', $admin))
            ->assertStatus(422);
    }

    public function test_it_demotes_an_admin_to_student(): void
    {
        $superAdmin = $this->superAdmin();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'a2@srru.ac.th']);

        $this->actingAs($superAdmin)
            ->post(route('admin.users.demote', $admin))
            ->assertRedirect();

        $this->assertSame('student', $admin->fresh()->role);
    }

    public function test_it_refuses_to_demote_yourself(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->post(route('admin.users.demote', $superAdmin))
            ->assertStatus(422);

        $this->assertSame('super_admin', $superAdmin->fresh()->role);
    }

    public function test_it_refuses_to_demote_the_last_super_admin(): void
    {
        $firstSuperAdmin = $this->superAdmin();
        $secondSuperAdmin = User::factory()->create(['role' => 'super_admin', 'email' => 'super2@srru.ac.th']);

        // Two super admins exist, so demoting the first one down to student
        // is allowed — it leaves exactly one behind.
        $this->actingAs($secondSuperAdmin)
            ->post(route('admin.users.demote', $firstSuperAdmin))
            ->assertRedirect();
        $this->assertSame('student', $firstSuperAdmin->fresh()->role);

        // Only $secondSuperAdmin is left, so the only way to reach this
        // route as a super_admin at all is to act as them — demoting
        // themselves must still be blocked for the "last one" reason.
        $this->actingAs($secondSuperAdmin)
            ->post(route('admin.users.demote', $secondSuperAdmin))
            ->assertStatus(422);
        $this->assertSame('super_admin', $secondSuperAdmin->fresh()->role);
    }

    public function test_it_bans_and_unbans_a_user(): void
    {
        $superAdmin = $this->superAdmin();
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($superAdmin)->post(route('admin.users.ban', $student))->assertRedirect();
        $this->assertSame('banned', $student->fresh()->account_status);

        $this->actingAs($superAdmin)->post(route('admin.users.unban', $student))->assertRedirect();
        $this->assertSame('active', $student->fresh()->account_status);
    }

    public function test_it_refuses_to_ban_yourself(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->post(route('admin.users.ban', $superAdmin))
            ->assertStatus(422);

        $this->assertSame('active', $superAdmin->fresh()->account_status);
    }
}

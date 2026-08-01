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
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $superAdmin->id,
            'action' => 'promoted',
            'subject_user_id' => $student->id,
        ]);
    }

    public function test_it_refuses_to_promote_someone_who_is_already_an_admin(): void
    {
        $superAdmin = $this->superAdmin();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'a2@srru.ac.th']);

        $this->actingAs($superAdmin)
            ->post(route('admin.users.promote', $admin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('admin', $admin->fresh()->role);
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
            ->assertRedirect()
            ->assertSessionHas('error');

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
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('super_admin', $secondSuperAdmin->fresh()->role);
    }

    public function test_it_bans_and_unbans_a_user(): void
    {
        $superAdmin = $this->superAdmin();
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($superAdmin)->post(route('admin.users.ban', $student))->assertRedirect();
        $this->assertSame('banned', $student->fresh()->account_status);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $superAdmin->id, 'action' => 'banned', 'subject_user_id' => $student->id]);

        $this->actingAs($superAdmin)->post(route('admin.users.unban', $student))->assertRedirect();
        $this->assertSame('active', $student->fresh()->account_status);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $superAdmin->id, 'action' => 'unbanned', 'subject_user_id' => $student->id]);
    }

    public function test_it_refuses_to_ban_yourself(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->post(route('admin.users.ban', $superAdmin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('active', $superAdmin->fresh()->account_status);
    }

    public function test_it_ungraduates_a_student(): void
    {
        $superAdmin = $this->superAdmin();
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th', 'graduated_at' => now()]);

        $this->actingAs($superAdmin)->post(route('admin.users.ungraduate', $student))->assertRedirect();
        $this->assertNull($student->fresh()->graduated_at);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $superAdmin->id, 'action' => 'ungraduated', 'subject_user_id' => $student->id]);
    }

    public function test_it_refuses_to_ungraduate_a_student_who_is_not_graduated(): void
    {
        $superAdmin = $this->superAdmin();
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($superAdmin)
            ->post(route('admin.users.ungraduate', $student))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_bulk_action_graduate_skips_an_already_graduated_student(): void
    {
        $superAdmin = $this->superAdmin();
        $fresh = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th']);
        $alreadyGraduated = User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th', 'graduated_at' => now()->subDay()]);

        $response = $this->actingAs($superAdmin)
            ->post(route('admin.users.bulk-action'), ['bulk_action' => 'graduate', 'user_ids' => [$fresh->id, $alreadyGraduated->id]]);

        $response->assertRedirect();
        $this->assertStringContainsString('1', session('status'));
    }

    public function test_bulk_action_ungraduates_several_students_at_once(): void
    {
        $superAdmin = $this->superAdmin();
        $a = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th', 'graduated_at' => now()]);
        $b = User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th', 'graduated_at' => now()]);

        $this->actingAs($superAdmin)
            ->post(route('admin.users.bulk-action'), ['bulk_action' => 'ungraduate', 'user_ids' => [$a->id, $b->id]])
            ->assertRedirect();

        $this->assertNull($a->fresh()->graduated_at);
        $this->assertNull($b->fresh()->graduated_at);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $superAdmin->id, 'action' => 'ungraduated', 'subject_user_id' => $a->id]);
    }

    public function test_bulk_action_ungraduate_skips_a_student_who_is_not_graduated(): void
    {
        $superAdmin = $this->superAdmin();
        $graduated = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th', 'graduated_at' => now()]);
        $notGraduated = User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th']);

        $response = $this->actingAs($superAdmin)
            ->post(route('admin.users.bulk-action'), ['bulk_action' => 'ungraduate', 'user_ids' => [$graduated->id, $notGraduated->id]]);

        $response->assertRedirect();
        $this->assertStringContainsString('1', session('status'));
    }

    public function test_bulk_action_promotes_several_students_at_once(): void
    {
        $superAdmin = $this->superAdmin();
        $a = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th']);
        $b = User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th']);

        $this->actingAs($superAdmin)
            ->post(route('admin.users.bulk-action'), ['bulk_action' => 'promote', 'user_ids' => [$a->id, $b->id]])
            ->assertRedirect();

        $this->assertSame('admin', $a->fresh()->role);
        $this->assertSame('admin', $b->fresh()->role);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $superAdmin->id, 'action' => 'promoted', 'subject_user_id' => $a->id]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $superAdmin->id, 'action' => 'promoted', 'subject_user_id' => $b->id]);
    }

    public function test_bulk_action_bans_several_students_at_once(): void
    {
        $superAdmin = $this->superAdmin();
        $a = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th']);
        $b = User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th']);

        $this->actingAs($superAdmin)
            ->post(route('admin.users.bulk-action'), ['bulk_action' => 'ban', 'user_ids' => [$a->id, $b->id]])
            ->assertRedirect();

        $this->assertSame('banned', $a->fresh()->account_status);
        $this->assertSame('banned', $b->fresh()->account_status);
    }

    public function test_bulk_action_graduates_several_students_at_once(): void
    {
        $superAdmin = $this->superAdmin();
        $a = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th']);
        $b = User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th']);

        $this->actingAs($superAdmin)
            ->post(route('admin.users.bulk-action'), ['bulk_action' => 'graduate', 'user_ids' => [$a->id, $b->id]])
            ->assertRedirect();

        $this->assertNotNull($a->fresh()->graduated_at);
        $this->assertNotNull($b->fresh()->graduated_at);
    }

    public function test_bulk_action_skips_rows_that_do_not_qualify_and_reports_the_skip_count(): void
    {
        $superAdmin = $this->superAdmin();
        $student = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th']);
        $alreadyAdmin = User::factory()->create(['role' => 'admin', 'email' => 'b@srru.ac.th']);

        $response = $this->actingAs($superAdmin)
            ->post(route('admin.users.bulk-action'), ['bulk_action' => 'promote', 'user_ids' => [$student->id, $alreadyAdmin->id]]);

        $response->assertRedirect();
        $this->assertSame('admin', $student->fresh()->role);
        $this->assertStringContainsString('1', session('status'));
    }

    public function test_bulk_action_excludes_yourself_from_a_ban(): void
    {
        $superAdmin = $this->superAdmin();
        $student = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th']);

        $this->actingAs($superAdmin)
            ->post(route('admin.users.bulk-action'), ['bulk_action' => 'ban', 'user_ids' => [$student->id, $superAdmin->id]])
            ->assertRedirect();

        $this->assertSame('banned', $student->fresh()->account_status);
        $this->assertSame('active', $superAdmin->fresh()->account_status);
    }

    public function test_a_plain_admin_cannot_use_the_bulk_action_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $student = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th']);

        $this->actingAs($admin)
            ->post(route('admin.users.bulk-action'), ['bulk_action' => 'ban', 'user_ids' => [$student->id]])
            ->assertForbidden();

        $this->assertSame('active', $student->fresh()->account_status);
    }

    public function test_it_sorts_by_email_ascending_overriding_the_role_grouping(): void
    {
        User::factory()->create(['role' => 'student', 'email' => 'zzz@srru.ac.th', 'name_thai' => 'Z']);
        User::factory()->create(['role' => 'student', 'email' => 'aaa@srru.ac.th', 'name_thai' => 'A']);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('admin.users.index', ['sort' => 'email', 'dir' => 'asc']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertTrue(strpos($content, 'aaa@srru.ac.th') < strpos($content, 'zzz@srru.ac.th'));
    }
}

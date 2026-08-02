<?php

namespace Tests\Feature\Admin;

use App\Models\CreditTransferPosition;
use App\Models\CreditTransferRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditTransferPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'email' => 'super@srru.ac.th']);
    }

    private function position(array $overrides = []): CreditTransferPosition
    {
        return CreditTransferPosition::create(array_merge([
            'key' => 'test_position_'.uniqid(),
            'label' => 'ตำแหน่งทดสอบ',
            'hours' => 40,
            'sort_order' => 99,
        ], $overrides));
    }

    public function test_a_plain_admin_cannot_manage_credit_transfer_positions(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        $this->actingAs($admin)->get(route('admin.credit-transfer-positions.index'))->assertForbidden();
    }

    public function test_it_seeds_the_original_seven_positions_via_migration(): void
    {
        $this->assertSame(7, CreditTransferPosition::count());
        $this->assertSame(50, CreditTransferPosition::where('key', 'class_leader')->value('hours'));
    }

    public function test_it_creates_a_position(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post(route('admin.credit-transfer-positions.store'), [
            'key' => 'vice_class_leader',
            'label' => 'รองหัวหน้าหมู่เรียน',
            'hours' => 40,
        ])->assertRedirect(route('admin.credit-transfer-positions.index'));

        $this->assertDatabaseHas('credit_transfer_positions', ['key' => 'vice_class_leader', 'hours' => 40]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $superAdmin->id, 'action' => 'created', 'title' => 'รองหัวหน้าหมู่เรียน']);
    }

    public function test_it_rejects_a_duplicate_key(): void
    {
        $superAdmin = $this->superAdmin();
        $this->position(['key' => 'duplicate_key']);

        $this->actingAs($superAdmin)->post(route('admin.credit-transfer-positions.store'), [
            'key' => 'duplicate_key',
            'label' => 'ตำแหน่งอื่น',
            'hours' => 20,
        ])->assertSessionHasErrors('key');
    }

    public function test_it_rejects_a_key_with_invalid_characters(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post(route('admin.credit-transfer-positions.store'), [
            'key' => 'Invalid Key!',
            'label' => 'ตำแหน่งอื่น',
            'hours' => 20,
        ])->assertSessionHasErrors('key');
    }

    public function test_it_updates_a_position(): void
    {
        $superAdmin = $this->superAdmin();
        $position = $this->position(['label' => 'ชื่อเดิม', 'hours' => 40]);

        $this->actingAs($superAdmin)->put(route('admin.credit-transfer-positions.update', $position), [
            'key' => $position->key,
            'label' => 'ชื่อใหม่',
            'hours' => 55,
        ])->assertRedirect();

        $position->refresh();
        $this->assertSame('ชื่อใหม่', $position->label);
        $this->assertSame(55, $position->hours);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $superAdmin->id, 'action' => 'updated', 'title' => 'ชื่อใหม่']);
    }

    public function test_updating_a_positions_hours_does_not_change_an_already_approved_request(): void
    {
        $superAdmin = $this->superAdmin();
        $position = $this->position(['hours' => 40]);
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);
        $request = CreditTransferRequest::create([
            'user_id' => $student->id,
            'position' => $position->key,
            'academic_year' => 2568,
            'hours_requested' => 40,
            'activity_category' => 'volunteer',
            'status' => 'approved',
        ]);

        $this->actingAs($superAdmin)->put(route('admin.credit-transfer-positions.update', $position), [
            'key' => $position->key,
            'label' => $position->label,
            'hours' => 60,
        ]);

        $this->assertSame(40, $request->fresh()->hours_requested);
    }

    public function test_it_refuses_to_rename_the_key_of_a_position_with_requests(): void
    {
        $superAdmin = $this->superAdmin();
        $position = $this->position(['key' => 'has_requests']);
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu3@srru.ac.th']);
        CreditTransferRequest::create([
            'user_id' => $student->id,
            'position' => $position->key,
            'academic_year' => 2568,
            'hours_requested' => $position->hours,
            'activity_category' => 'volunteer',
            'status' => 'approved',
        ]);

        $this->actingAs($superAdmin)->put(route('admin.credit-transfer-positions.update', $position), [
            'key' => 'renamed_key',
            'label' => $position->label,
            'hours' => $position->hours,
        ])->assertSessionHasErrors('key');

        $this->assertSame('has_requests', $position->fresh()->key);
    }

    public function test_it_still_allows_editing_label_and_hours_when_the_key_is_locked(): void
    {
        $superAdmin = $this->superAdmin();
        $position = $this->position(['key' => 'has_requests_2']);
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu4@srru.ac.th']);
        CreditTransferRequest::create([
            'user_id' => $student->id,
            'position' => $position->key,
            'academic_year' => 2568,
            'hours_requested' => $position->hours,
            'activity_category' => 'volunteer',
            'status' => 'approved',
        ]);

        // Same key resubmitted (unchanged) — should not trip the lock.
        $this->actingAs($superAdmin)->put(route('admin.credit-transfer-positions.update', $position), [
            'key' => 'has_requests_2',
            'label' => 'ชื่อใหม่ที่แก้ได้',
            'hours' => 45,
        ])->assertRedirect(route('admin.credit-transfer-positions.index'));

        $position->refresh();
        $this->assertSame('ชื่อใหม่ที่แก้ได้', $position->label);
        $this->assertSame(45, $position->hours);
    }

    public function test_it_allows_renaming_the_key_of_a_position_with_no_requests(): void
    {
        $superAdmin = $this->superAdmin();
        $position = $this->position(['key' => 'unused_key']);

        $this->actingAs($superAdmin)->put(route('admin.credit-transfer-positions.update', $position), [
            'key' => 'renamed_freely',
            'label' => $position->label,
            'hours' => $position->hours,
        ])->assertRedirect(route('admin.credit-transfer-positions.index'));

        $this->assertSame('renamed_freely', $position->fresh()->key);
    }

    public function test_it_refuses_to_delete_a_position_with_requests(): void
    {
        $superAdmin = $this->superAdmin();
        $position = $this->position();
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu2@srru.ac.th']);
        CreditTransferRequest::create([
            'user_id' => $student->id,
            'position' => $position->key,
            'academic_year' => 2568,
            'hours_requested' => $position->hours,
            'status' => 'pending',
        ]);

        $this->actingAs($superAdmin)->delete(route('admin.credit-transfer-positions.destroy', $position))->assertRedirect();

        $this->assertDatabaseHas('credit_transfer_positions', ['id' => $position->id]);
    }

    public function test_it_deletes_an_unused_position(): void
    {
        $superAdmin = $this->superAdmin();
        $position = $this->position(['label' => 'ตำแหน่งจะลบ']);

        $this->actingAs($superAdmin)
            ->delete(route('admin.credit-transfer-positions.destroy', $position))
            ->assertRedirect(route('admin.credit-transfer-positions.index'));

        $this->assertDatabaseMissing('credit_transfer_positions', ['id' => $position->id]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $superAdmin->id, 'action' => 'deleted', 'title' => 'ตำแหน่งจะลบ']);
    }
}

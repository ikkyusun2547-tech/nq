<?php

namespace Tests\Feature\Admin;

use App\Models\Faculty;
use App\Models\Major;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyManagementTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'email' => 'super@srru.ac.th']);
    }

    public function test_a_plain_admin_cannot_manage_faculties(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        $this->actingAs($admin)->get(route('admin.faculties.index'))->assertForbidden();
    }

    public function test_it_creates_a_faculty(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post(route('admin.faculties.store'), [
            'code' => 'ENG',
            'name_th' => 'คณะวิศวกรรมศาสตร์',
            'name_en' => 'Faculty of Engineering',
        ])->assertRedirect(route('admin.faculties.index'));

        $this->assertDatabaseHas('faculties', ['code' => 'ENG', 'name_th' => 'คณะวิศวกรรมศาสตร์']);
    }

    public function test_it_rejects_a_duplicate_faculty_code(): void
    {
        $superAdmin = $this->superAdmin();
        Faculty::factory()->create(['code' => 'ENG']);

        $this->actingAs($superAdmin)->post(route('admin.faculties.store'), [
            'code' => 'ENG',
            'name_th' => 'คณะอื่น',
        ])->assertSessionHasErrors('code');
    }

    public function test_it_updates_a_faculty(): void
    {
        $superAdmin = $this->superAdmin();
        $faculty = Faculty::factory()->create(['name_th' => 'ชื่อเดิม']);

        $this->actingAs($superAdmin)->put(route('admin.faculties.update', $faculty), [
            'code' => $faculty->code,
            'name_th' => 'ชื่อใหม่',
        ])->assertRedirect();

        $this->assertSame('ชื่อใหม่', $faculty->fresh()->name_th);
    }

    public function test_it_refuses_to_delete_a_faculty_with_students(): void
    {
        $superAdmin = $this->superAdmin();
        $faculty = Faculty::factory()->create();
        User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th', 'faculty_id' => $faculty->id]);

        $this->actingAs($superAdmin)->delete(route('admin.faculties.destroy', $faculty))->assertRedirect();

        $this->assertDatabaseHas('faculties', ['id' => $faculty->id]);
    }

    public function test_it_deletes_an_empty_faculty(): void
    {
        $superAdmin = $this->superAdmin();
        $faculty = Faculty::factory()->create();

        $this->actingAs($superAdmin)
            ->delete(route('admin.faculties.destroy', $faculty))
            ->assertRedirect(route('admin.faculties.index'));

        $this->assertDatabaseMissing('faculties', ['id' => $faculty->id]);
    }

    public function test_it_adds_a_major_to_a_faculty(): void
    {
        $superAdmin = $this->superAdmin();
        $faculty = Faculty::factory()->create();

        $this->actingAs($superAdmin)->post(route('admin.majors.store', $faculty), [
            'code' => 'CS01',
            'name_th' => 'สาขาวิทยาการคอมพิวเตอร์',
            'degree_abbr' => 'วท.บ.',
        ])->assertRedirect();

        $this->assertDatabaseHas('majors', [
            'faculty_id' => $faculty->id,
            'code' => 'CS01',
            'name_th' => 'สาขาวิทยาการคอมพิวเตอร์',
        ]);
    }

    public function test_it_refuses_to_delete_a_major_with_students(): void
    {
        $superAdmin = $this->superAdmin();
        $major = Major::factory()->create();
        User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th', 'faculty_id' => $major->faculty_id, 'major_id' => $major->id]);

        $this->actingAs($superAdmin)->delete(route('admin.majors.destroy', $major))->assertRedirect();

        $this->assertDatabaseHas('majors', ['id' => $major->id]);
    }

    public function test_it_deletes_an_empty_major(): void
    {
        $superAdmin = $this->superAdmin();
        $major = Major::factory()->create();

        $this->actingAs($superAdmin)->delete(route('admin.majors.destroy', $major))->assertRedirect();

        $this->assertDatabaseMissing('majors', ['id' => $major->id]);
    }
}

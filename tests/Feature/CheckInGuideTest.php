<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckInGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_sees_every_check_in_method_with_student_links(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'stu'.uniqid().'@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => '12345678901',
            'year_level' => 2,
            'program_type' => 'normal',
        ]);

        $this->actingAs($student)->get(route('checkin-guide'))
            ->assertOk()
            ->assertSeeInOrder(['เช็คชื่อหน้างานใน 3 ขั้นตอน', 'แนบรูปหลักฐานใน 3 ขั้นตอน', 'ขอเช็คชื่อย้อนหลัง', 'เงื่อนไข', 'ขอชั่วโมงกิจกรรมภายนอก', 'ไม่เกิน 10 ชั่วโมงต่อปีการศึกษา', 'เทียบโอนชั่วโมงจากตำแหน่ง', 'รอตรวจสอบ'])
            ->assertSee(route('checkin.show'), false)
            // A loop variable named $title once leaked into the layout's <title>.
            ->assertSee('<title>ระบบเช็คชื่อกิจกรรมนักศึกษา SRRU</title>', false);
    }

    public function test_an_admin_can_read_it_without_student_only_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'adm'.uniqid().'@srru.ac.th']);

        $this->actingAs($admin)->get(route('checkin-guide'))
            ->assertOk()
            ->assertSee('เช็คชื่อหน้างานใน 3 ขั้นตอน')
            ->assertDontSee(route('checkin.show'), false);
    }

    public function test_guests_are_sent_to_log_in(): void
    {
        $this->get(route('checkin-guide'))->assertRedirect();
    }
}

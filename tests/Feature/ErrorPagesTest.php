<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unknown_url_gets_the_thai_404_page(): void
    {
        app()->setLocale('th');

        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('ไม่พบหน้าที่ต้องการ')
            ->assertSee('กลับหน้าแรก')
            ->assertDontSee('Not Found');
    }

    public function test_a_student_on_an_admin_page_gets_the_403_page_with_their_account(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.dashboard'))
            ->assertForbidden()
            ->assertSee('ไม่มีสิทธิ์เข้าถึงหน้านี้')
            ->assertSee('stu@srru.ac.th');
    }

    /** These must render on their own: no session/database needed when the error is the database. */
    public function test_the_other_error_views_render_standalone(): void
    {
        foreach (['419' => 'หน้านี้หมดเวลาแล้ว', '429' => 'ส่งคำขอถี่เกินไป', '500' => 'ระบบขัดข้องชั่วคราว', '503' => 'กำลังปรับปรุงระบบ'] as $code => $title) {
            $html = view("errors.$code")->render();
            $this->assertStringContainsString($title, $html, "errors.$code");
            $this->assertStringContainsString($code, $html, "errors.$code");
        }
    }

    public function test_the_offline_page_exists_and_is_precached_by_the_service_worker(): void
    {
        $this->assertFileExists(public_path('offline.html'));
        $this->assertStringContainsString('ไม่มีการเชื่อมต่ออินเทอร์เน็ต', file_get_contents(public_path('offline.html')));
        $sw = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString("OFFLINE_URL = '/offline.html'", $sw);
        $this->assertStringContainsString("request.mode === 'navigate'", $sw);
    }
}

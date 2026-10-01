<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * x-data lives in a double-quoted HTML attribute, so a stray `"` inside it
 * (e.g. in a JS comment) silently cuts the object short and Alpine throws
 * "Unexpected token" in the browser — something no server-side assertion
 * would otherwise notice. Render the pages that use the shared filter
 * sheet and check every x-data object is complete.
 */
class AlpineAttributesTest extends TestCase
{
    use RefreshDatabase;

    private function assertXDataComplete(string $html, string $page): void
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        $xpath = new \DOMXPath($dom);

        $checked = 0;
        foreach ($xpath->query('//*[@x-data]') as $node) {
            $value = trim($node->getAttribute('x-data'));
            if (! str_starts_with($value, '{')) {
                continue;
            }
            $checked++;
            $this->assertSame(
                substr_count($value, '{'),
                substr_count($value, '}'),
                "Unbalanced x-data on {$page}: ".mb_substr($value, 0, 120)
            );
            $this->assertStringEndsWith('}', $value, "Truncated x-data on {$page}: ".mb_substr($value, 0, 120));
        }

        $this->assertGreaterThan(0, $checked, "No x-data objects found on {$page}");
    }

    public function test_pages_with_the_filter_sheet_render_complete_alpine_objects(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'email' => 'admin@srru.ac.th']);
        $student = User::factory()->create([
            'role' => 'student', 'email' => 'stu@srru.ac.th', 'faculty_id' => Faculty::factory(),
            'student_id' => '12345678901', 'year_level' => 2, 'program_type' => 'normal',
        ]);
        $activity = Activity::factory()->create();

        $pages = [
            [$admin, route('admin.activities.index')],
            [$admin, route('admin.students.index')],
            [$admin, route('admin.attendance.index', $activity)],
            [$student, route('activities.index')],
        ];

        foreach ($pages as [$user, $url]) {
            $html = $this->actingAs($user)->get($url)->assertOk()->getContent();
            $this->assertXDataComplete($html, $url);
        }
    }
}

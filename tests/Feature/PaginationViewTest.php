<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use App\Services\AcademicYearCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_paginator_speaks_thai_and_marks_the_current_page(): void
    {
        app()->setLocale('th');
        Activity::factory()->count(25)->create(['academic_year' => AcademicYearCalculator::forDate(now())]);
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        $this->actingAs($admin)->get(route('admin.activities.index', ['page' => 2]))
            ->assertOk()
            ->assertSeeInOrder(['แสดง', '21–25', 'จากทั้งหมด', '25', 'รายการ'])
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('Showing');
    }
}

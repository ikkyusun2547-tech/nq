<?php

namespace Tests\Feature\Api;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TranscriptTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_downloads_a_pdf_for_an_authenticated_student(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'email' => 'student@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => '12345678901',
            'year_level' => 2,
            'program_type' => 'normal',
        ]);

        Sanctum::actingAs($user);

        $response = $this->get('/api/transcript');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }
}

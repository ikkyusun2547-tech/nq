<?php

namespace Tests\Feature\Api;

use App\Models\Faculty;
use App\Models\User;
use App\Services\AcademicYearCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreditTransferTest extends TestCase
{
    use RefreshDatabase;

    private function studentUser(): User
    {
        return User::factory()->create([
            'role' => 'student',
            'email' => 'student@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => '12345678901',
            'year_level' => 2,
            'program_type' => 'normal',
        ]);
    }

    public function test_it_creates_a_pending_request_with_position_hours(): void
    {
        Storage::fake('public');
        $user = $this->studentUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/credit-transfers', [
            'position' => 'class_leader',
            'academic_year' => AcademicYearCalculator::forDate(now()),
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('credit_transfer_requests', [
            'user_id' => $user->id,
            'position' => 'class_leader',
            'hours_requested' => 50,
            'status' => 'pending',
        ]);
    }

    public function test_it_accepts_a_pdf_as_proof(): void
    {
        Storage::fake('public');
        $user = $this->studentUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/credit-transfers', [
            'position' => 'class_leader',
            'academic_year' => AcademicYearCalculator::forDate(now()),
            'proof_image' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('credit_transfer_requests', [
            'user_id' => $user->id,
            'position' => 'class_leader',
            'hours_requested' => 50,
            'status' => 'pending',
        ]);
    }

    public function test_it_rejects_a_second_claim_in_the_same_academic_year(): void
    {
        Storage::fake('public');
        $user = $this->studentUser();
        Sanctum::actingAs($user);
        $year = AcademicYearCalculator::forDate(now());

        $this->postJson('/api/credit-transfers', [
            'position' => 'class_leader',
            'academic_year' => $year,
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertOk();

        $this->postJson('/api/credit-transfers', [
            'position' => 'class_representative',
            'academic_year' => $year,
            'proof_image' => UploadedFile::fake()->image('proof2.jpg'),
        ])->assertStatus(422)->assertJsonValidationErrors('academic_year');
    }

    public function test_positions_endpoint_lists_all_positions_with_hours(): void
    {
        $user = $this->studentUser();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/credit-transfers/positions');

        $response->assertOk();
        $this->assertCount(7, $response->json('data'));
        $this->assertContains(
            ['key' => 'class_leader', 'label' => 'หัวหน้าหมู่เรียน', 'hours' => 50],
            $response->json('data')
        );
    }
}

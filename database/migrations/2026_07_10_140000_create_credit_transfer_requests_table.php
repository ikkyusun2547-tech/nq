<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credit_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('position', [
                'student_council_president',
                'student_club_president',
                'student_parliament_president',
                'club_president',
                'dormitory_president',
                'class_leader',
                'class_representative',
            ]);
            $table->unsignedSmallInteger('academic_year');
            $table->unsignedSmallInteger('hours_requested');
            $table->unsignedSmallInteger('hours_approved')->nullable();
            $table->enum('activity_category', ['culture', 'academic', 'sports', 'volunteer', 'ethics'])->nullable();
            $table->string('proof_image_path');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('reject_reason')->nullable();
            $table->text('admin_comment')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'academic_year']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transfer_requests');
    }
};

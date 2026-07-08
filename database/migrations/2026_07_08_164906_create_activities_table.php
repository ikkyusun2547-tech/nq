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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('banner_url')->nullable();
            $table->string('organizer_name')->nullable();
            $table->string('dress_code')->nullable();
            $table->enum('activity_level', ['university', 'faculty'])->default('university');
            $table->enum('activity_category', ['culture', 'academic', 'sports', 'volunteer', 'ethics']);
            $table->enum('activity_type', ['core', 'elective'])->default('elective');
            $table->unsignedSmallInteger('credit_hours');
            $table->unsignedInteger('capacity')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->decimal('location_lat', 10, 8);
            $table->decimal('location_lng', 11, 8);
            $table->unsignedInteger('allowed_radius')->default(100);
            $table->string('qr_secret');
            $table->enum('status', ['draft', 'open', 'full', 'ongoing', 'closed', 'cancelled'])->default('draft');
            $table->timestamps();

            $table->index('status');
            $table->index('activity_category');
            $table->index(['start_at', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};

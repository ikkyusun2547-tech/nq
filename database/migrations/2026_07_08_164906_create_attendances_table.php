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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->dateTime('checkin_time');
            $table->decimal('student_lat', 10, 8);
            $table->decimal('student_lng', 11, 8);
            $table->unsignedInteger('distance_meters')->nullable();
            $table->string('device_uuid');
            $table->string('photo_path');
            $table->enum('status', ['auto_approved', 'flagged', 'rejected'])->default('flagged');
            $table->text('flag_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'activity_id']);
            $table->index(['activity_id', 'device_uuid']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

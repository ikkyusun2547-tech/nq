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
        Schema::create('announcement_logs', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('body');
            // Both null here means "sent to everyone" — matches the same
            // nullable-filter convention AnnouncementController::store()
            // already uses to build the recipient query.
            $table->foreignId('faculty_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('year_level')->nullable();
            $table->unsignedInteger('recipient_count');
            $table->foreignId('sent_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_logs');
    }
};

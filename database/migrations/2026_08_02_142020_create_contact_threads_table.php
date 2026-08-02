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
        Schema::create('contact_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->enum('status', ['open', 'closed'])->default('open');
            // Where the student came from when they opened this thread (a
            // flagged check-in, a rejected request, ...) — freeform, purely
            // informational, no FK since it can point at several tables.
            $table->string('context_type')->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            // One shared flag per side, not a per-admin read-receipts table —
            // this queue is worked by whichever admin gets to it, same as
            // external-activities/credit-transfers/late-checkins.
            $table->boolean('student_unread')->default(false);
            $table->boolean('admin_unread')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_threads');
    }
};

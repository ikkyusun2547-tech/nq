<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per (activity, kind) of one-off notification already sent —
 * "check-in is open", "closing in an hour", "starts in an hour", ... — so
 * the scheduler can run every few minutes without ever sending the same
 * reminder twice. The unique index is the guard: claiming a kind is an
 * insert-or-ignore, which stays correct even if two runs overlap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 40);
            $table->timestamp('sent_at')->useCurrent();
            $table->unique(['activity_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_notification_logs');
    }
};

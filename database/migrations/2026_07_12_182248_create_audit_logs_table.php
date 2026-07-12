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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            // e.g. 'promoted', 'banned', 'created', 'updated', 'deleted' — a
            // fixed vocabulary the admin audit-log page already has a
            // badge/label for, shared with the existing approve/reject feed.
            $table->string('action');
            // Precomputed Thai category ("ผู้ใช้งาน", "คณะ", ...) and a
            // one-line description ("เลื่อนสิทธิ์ ... เป็นแอดมิน") — stored as
            // plain text rather than derived from a polymorphic subject so
            // the log still reads correctly even after the subject itself
            // is renamed or deleted.
            $table->string('type_label');
            $table->string('title');
            // Only set when the action targets a specific user (promote/
            // demote/ban/unban) — null for faculty/major/settings changes.
            $table->foreignId('subject_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

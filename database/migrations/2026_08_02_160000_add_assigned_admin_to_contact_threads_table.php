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
        Schema::table('contact_threads', function (Blueprint $table) {
            // Soft "claim" so admins can see who's already handling a
            // thread — doesn't restrict who may reply, just who's shown as
            // currently on it. Null means nobody has claimed it yet.
            $table->foreignId('assigned_admin_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_threads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_admin_id');
        });
    }
};

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
        Schema::create('activity_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('target_year')->nullable();
            $table->timestamps();

            $table->index(['activity_id', 'faculty_id', 'major_id', 'target_year'], 'activity_restrictions_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_restrictions');
    }
};

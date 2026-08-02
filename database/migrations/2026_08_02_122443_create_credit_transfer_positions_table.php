<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credit_transfer_positions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->unsignedSmallInteger('hours');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed with the values that used to live in
        // CreditTransferRequest::POSITION_HOURS/POSITION_LABELS, in the same
        // order, so existing behavior is unchanged the moment this deploys.
        $positions = [
            ['key' => 'student_council_president', 'label' => 'นายกองค์การบริหารนักศึกษา', 'hours' => 60],
            ['key' => 'student_club_president', 'label' => 'นายกสโมสรนักศึกษา', 'hours' => 60],
            ['key' => 'student_parliament_president', 'label' => 'ประธานสภานักศึกษา', 'hours' => 60],
            ['key' => 'club_president', 'label' => 'ประธานชมรม', 'hours' => 50],
            ['key' => 'dormitory_president', 'label' => 'ประธานหอพักมหาวิทยาลัย', 'hours' => 50],
            ['key' => 'class_leader', 'label' => 'หัวหน้าหมู่เรียน', 'hours' => 50],
            ['key' => 'class_representative', 'label' => 'ตัวแทนหมู่เรียน', 'hours' => 50],
        ];

        foreach ($positions as $index => $position) {
            DB::table('credit_transfer_positions')->insert(array_merge($position, [
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transfer_positions');
    }
};

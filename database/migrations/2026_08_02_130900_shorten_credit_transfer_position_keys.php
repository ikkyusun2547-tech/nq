<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * key => [old, new] — shortens the original position keys (kept long
     * and awkward from the initial constants-to-table migration) to
     * something a human can actually read at a glance. Updates both
     * credit_transfer_positions.key and any credit_transfer_requests.position
     * rows already referencing the old value, so no historical row is left
     * pointing at a key that no longer exists.
     */
    private const RENAMES = [
        'student_council_president' => 'council_president',
        'student_club_president' => 'union_president',
        'student_parliament_president' => 'parliament_president',
        'dormitory_president' => 'dorm_president',
        'class_representative' => 'class_rep',
        // club_president and class_leader are unchanged — already short.
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::RENAMES as $old => $new) {
            DB::table('credit_transfer_positions')->where('key', $old)->update(['key' => $new]);
            DB::table('credit_transfer_requests')->where('position', $old)->update(['position' => $new]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::RENAMES as $old => $new) {
            DB::table('credit_transfer_positions')->where('key', $new)->update(['key' => $old]);
            DB::table('credit_transfer_requests')->where('position', $new)->update(['position' => $old]);
        }
    }
};

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
        Schema::table('credit_transfer_requests', function (Blueprint $table) {
            // Was a fixed 7-value enum — now that positions are configurable
            // (credit_transfer_positions, admin-managed), a plain string lets
            // an admin add/rename/remove a position without a matching
            // schema migration every time. Existing enum values are already
            // valid strings, so this is a no-op for existing rows.
            $table->string('position', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Deliberately NOT reverted to an enum: positions are admin-configurable
     * now (credit_transfer_positions), so by the time this ever runs there
     * may be added or renamed keys with no matching enum value to fall back
     * to — a best-effort enum() here would silently corrupt or truncate
     * that data instead of failing loudly. Left as a string; rolling back
     * this migration is a no-op on the column itself.
     */
    public function down(): void
    {
        //
    }
};

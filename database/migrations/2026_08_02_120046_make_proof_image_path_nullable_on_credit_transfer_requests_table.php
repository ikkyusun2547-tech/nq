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
            // Admin-granted rows (CreditTransferApprovalController::grant())
            // don't necessarily have a student-uploaded proof image — only
            // the student-submitted flow (CreditTransferStoreRequest) still
            // requires one, enforced at the validation layer.
            $table->string('proof_image_path')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_transfer_requests', function (Blueprint $table) {
            $table->string('proof_image_path')->nullable(false)->change();
        });
    }
};

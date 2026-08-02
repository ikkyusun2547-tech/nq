<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            // Facebook-style: a message can be just an image/file with no
            // caption, so body can no longer be required at the DB level —
            // the "at least one of body/attachment" rule lives in request
            // validation instead.
            $table->text('body')->nullable()->change();

            $table->string('attachment_path')->nullable()->after('body');
            // Original filename — attachment_path is a randomized storage
            // name, not fit to show a user.
            $table->string('attachment_name')->nullable()->after('attachment_path');
            // Drives image-vs-file-chip rendering client-side without
            // guessing from the file extension.
            $table->string('attachment_mime')->nullable()->after('attachment_name');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_mime']);
        });

        // body deliberately stays nullable — by the time this ever rolls
        // back there may already be attachment-only rows with no body, and
        // forcing it back to NOT NULL would fail loudly (or truncate) on
        // exactly that data. Same reasoning as the position-to-string
        // migration's down() earlier this session.
    }
};

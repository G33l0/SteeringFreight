<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Files can no longer be sent through the customer chat.
 *
 * The chat window is deliberately short lived and holds nothing that has to be
 * kept, so anything a customer already uploaded through it is deleted with the
 * columns that pointed at it. Documents that belong to a shipment are uploaded
 * by staff and live with the shipment instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Storage::disk('local')->deleteDirectory('chat');

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size']);
        });
    }

    /**
     * The columns come back empty: the files themselves were deleted above and
     * cannot be restored.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime', 160)->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
        });
    }
};

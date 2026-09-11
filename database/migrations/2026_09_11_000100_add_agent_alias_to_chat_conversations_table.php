<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The first name the customer sees once somebody joins their chat.
 *
 * Stored on the conversation rather than worked out per message, so the person
 * answering keeps the same name for the whole thread. Until it is set, nobody
 * has joined yet and the customer is shown the waiting notice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->string('agent_alias', 40)->nullable()->after('contact_email');
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropColumn('agent_alias');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject')->nullable();
            $table->string('contact_name');
            $table->string('contact_email');
            // Random identifier used in customer facing URLs. The visitor also
            // has to hold the matching token in their session, so the value on
            // its own does not grant access.
            $table->string('public_token', 64)->unique();
            $table->string('status', 20)->default('open');
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('unread_for_staff')->default(0);
            $table->unsignedInteger('unread_for_customer')->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'last_message_at'], 'conversations_status_activity_index');
            $table->index('shipment_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_status_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location')->nullable();
            $table->timestamp('occurred_at');
            $table->text('description')->nullable();
            // Never rendered on the public tracking page.
            $table->text('internal_note')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('notified_customer')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shipment_id', 'occurred_at'], 'events_shipment_occurred_index');
            $table->index(['shipment_id', 'is_public'], 'events_shipment_public_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
    }
};

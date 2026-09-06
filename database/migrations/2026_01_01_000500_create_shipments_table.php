<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number', 40)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 40)->nullable();

            $table->string('origin_country', 120)->nullable();
            $table->string('origin_city', 120)->nullable();
            $table->string('destination_country', 120)->nullable();
            $table->string('destination_city', 120)->nullable();
            $table->string('current_location')->nullable();

            $table->string('shipping_method', 30)->nullable();
            $table->string('service_level', 60)->nullable();
            $table->text('cargo_description')->nullable();
            $table->unsignedInteger('package_count')->nullable();
            $table->decimal('weight_kg', 12, 3)->nullable();
            $table->string('dimensions', 120)->nullable();
            $table->decimal('declared_value', 14, 2)->nullable();
            $table->string('declared_value_currency', 3)->nullable();

            $table->string('container_number', 40)->nullable();
            $table->string('vessel_name', 120)->nullable();
            $table->string('voyage_number', 60)->nullable();
            $table->string('air_waybill_number', 60)->nullable();
            $table->string('flight_number', 40)->nullable();
            $table->string('bill_of_lading_number', 60)->nullable();

            $table->date('estimated_departure')->nullable();
            $table->date('estimated_arrival')->nullable();
            $table->date('estimated_delivery')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->foreignId('shipment_status_id')->nullable()->constrained()->nullOnDelete();
            // Cached milestone position so the progress bar does not need to
            // walk the whole event history on every tracking page view.
            $table->unsignedSmallInteger('progress_stage')->default(0);
            $table->text('exception_note')->nullable();

            $table->text('internal_notes')->nullable();
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('is_sample')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('shipment_status_id');
            $table->index('customer_id');
            $table->index('shipping_method');
            $table->index('archived_at');
            $table->index('created_at');
            $table->index('updated_at');
            $table->index(['origin_country', 'origin_city'], 'shipments_origin_index');
            $table->index(['destination_country', 'destination_city'], 'shipments_destination_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};

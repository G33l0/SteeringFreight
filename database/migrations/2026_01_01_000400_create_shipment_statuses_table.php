<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('category', 20)->default('milestone');
            // Milestone position on the tracking timeline. Exceptions have no
            // position of their own; they are shown against the last milestone.
            $table->unsignedSmallInteger('stage')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('colour', 20)->default('slate');
            $table->string('customer_label', 160)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_final')->default(false);
            $table->boolean('notify_customer')->default(false);
            // Exception statuses that must carry an administrator written
            // explanation before they can be applied to a shipment.
            $table->boolean('requires_explanation')->default(false);
            $table->timestamps();

            $table->index(['category', 'sort_order'], 'statuses_category_order_index');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_statuses');
    }
};

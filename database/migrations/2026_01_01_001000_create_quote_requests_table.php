<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('company')->nullable();
            $table->string('origin');
            $table->string('destination');
            $table->string('shipping_method', 30)->nullable();
            $table->string('cargo_type', 160)->nullable();
            $table->string('approximate_weight', 60)->nullable();
            $table->unsignedInteger('package_count')->nullable();
            $table->date('ready_date')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new');
            $table->text('internal_notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'quotes_status_created_index');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};

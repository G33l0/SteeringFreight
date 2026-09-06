<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('body');
            $table->string('photo_path')->nullable();
            $table->string('service_used', 120)->nullable();
            $table->boolean('is_published')->default(false);
            // Records created by the demo seeder. Sample entries are labelled
            // as sample content wherever they are displayed.
            $table->boolean('is_sample')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->date('reviewed_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_published', 'sort_order'], 'reviews_published_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

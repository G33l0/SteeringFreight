<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('summary', 400);
            $table->longText('description')->nullable();
            $table->string('icon', 40)->default('container');
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->json('highlights')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->boolean('show_on_home')->default(true);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->timestamps();

            $table->index(['is_published', 'sort_order'], 'services_published_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 40)->default('other');
            $table->text('description')->nullable();
            $table->string('original_name');
            // Path on the private disk. Files are never served directly.
            $table->string('path');
            $table->string('mime_type', 160)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('visibility', 20)->default('internal');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shipment_id', 'visibility'], 'documents_shipment_visibility_index');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_documents');
    }
};

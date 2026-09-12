<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secure_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');
            $table->string('category', 64);
            $table->string('variant', 32)->default('original');
            $table->foreignId('parent_id')->nullable()->constrained('secure_files')->nullOnDelete();
            $table->string('disk', 64);
            $table->string('path');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type', 127);
            $table->string('extension', 16);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['attachable_type', 'attachable_id', 'category', 'variant'], 'secure_files_attachable_lookup');
            $table->index('sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secure_files');
    }
};

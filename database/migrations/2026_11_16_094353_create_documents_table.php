<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations');
            $table->foreignUuid('folder_id')->constrained('folders');
            $table->uuidMorphs('uploader');
            $table->string('name');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('category');
            $table->string('period')->nullable();
            $table->string('confidentiality_level')->default('standard'); // standard, restreint, confidentiel
            $table->foreignUuid('parent_document_id')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('is_published_to_client')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignUuid('published_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('parent_document_id')
                ->references('id')
                ->on('documents')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

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
        Schema::create('document_request_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_request_id')
                ->constrained('document_requests')
                ->onDelete('cascade');
            $table->uuidMorphs('sender');
            $table->text('message')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_request_messages');
    }
};

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
        Schema::create('document_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('folder_id')->constrained('folders')->onDelete('cascade');
            $table->foreignUuid('requested_by')->constrained('users'); // Membre du cabinet qui initie la demande
            $table->string('reference')->unique(); // Numéro unique de la demande (ex: DEM-2026-001)
            $table->string('title');               // Objet de la demande (ex: "Relevés bancaires T3")
            $table->foreignUuid('status_id')->constrained('statuses');
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};

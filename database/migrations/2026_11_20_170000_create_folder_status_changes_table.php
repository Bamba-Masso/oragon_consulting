<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historique des statuts d'un dossier : chaque transition est horodatée et attribuée à son auteur.
     */
    public function up(): void
    {
        Schema::create('folder_status_changes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('folder_id')->constrained('folders')->cascadeOnDelete();
            $table->foreignUuid('from_status_id')->nullable()->constrained('statuses');
            $table->foreignUuid('to_status_id')->constrained('statuses');
            $table->foreignUuid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['folder_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_status_changes');
    }
};

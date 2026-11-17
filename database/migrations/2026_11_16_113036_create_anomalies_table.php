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
        Schema::create('anomalies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('folder_id')->constrained('folders')->onDelete('cascade');
            $table->foreignUuid('reported_by')->constrained('users');
            $table->string('pole');
            $table->string('title');
            $table->text('description');
            $table->enum('risk_level', ['faible', 'moyen', 'eleve', 'critique']);
            $table->foreignUuid('status_id')->constrained('statuses');
            $table->boolean('is_visible_to_client')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anomalies');
    }
};

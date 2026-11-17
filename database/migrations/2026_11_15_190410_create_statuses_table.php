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
        Schema::create('statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('status_type_id')->constrained('status_types');
            $table->string('name');
            $table->string('code');
            $table->string('color')->nullable();
            $table->timestamps();

            $table->unique(['status_type_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};

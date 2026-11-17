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
        Schema::create('folders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations');
            $table->foreignUuid('assigned_manager_id')->nullable()->constrained('users');
            $table->foreignUuid('status_id')->constrained('statuses');
            $table->foreignUuid('priority_id')->constrained('priorities');
            $table->string('reference')->unique();
            $table->string('title');
            $table->string('mission_type');
            $table->string('period')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};

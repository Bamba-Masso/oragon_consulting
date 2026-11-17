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
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('folder_id')->constrained('folders');
            $table->foreignUuid('assigned_to')->nullable()->constrained('users');
            $table->foreignUuid('priority_id')->constrained('priorities');
            $table->foreignUuid('depends_on_task_id')->nullable();
            $table->string('pole'); // Secrétariat, Comptabilité, Fiscalité, Finance, Contrôle/Audit
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('status_id')->constrained('statuses');
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreign('depends_on_task_id')
                ->references('id')
                ->on('tasks')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le pôle d'une tâche se déduit du rôle du collaborateur à qui elle est assignée.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('pole');
        });
    }

    /**
     * Reverse the migrations : la colonne revient vide.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('pole')->nullable()->after('depends_on_task_id');
        });
    }
};

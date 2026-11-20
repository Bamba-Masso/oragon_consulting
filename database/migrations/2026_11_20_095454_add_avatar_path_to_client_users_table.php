<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Photo de profil de l'utilisateur du portail (chemin sur le disque « public »).
     */
    public function up(): void
    {
        Schema::table('client_users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('job_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_users', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};

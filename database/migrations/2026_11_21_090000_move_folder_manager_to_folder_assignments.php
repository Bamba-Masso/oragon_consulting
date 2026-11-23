<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Le responsable du dossier devient un affecté marqué « is_manager » dans folder_assignments :
     * - ajout de is_manager et recopie des responsables existants (folders.assigned_manager_id) ;
     * - suppression de folders.assigned_manager_id ;
     * - suppression de folder_assignments.pole (le pôle se déduit du rôle du collaborateur).
     */
    public function up(): void
    {
        Schema::table('folder_assignments', function (Blueprint $table) {
            $table->boolean('is_manager')->default(false)->after('user_id');
        });

        DB::table('folders')->whereNotNull('assigned_manager_id')->orderBy('id')->each(function (object $folder): void {
            $assignment = DB::table('folder_assignments')
                ->where('folder_id', $folder->id)
                ->where('user_id', $folder->assigned_manager_id);

            if ($assignment->exists()) {
                $assignment->update(['is_manager' => true]);

                return;
            }

            DB::table('folder_assignments')->insert([
                'id' => (string) Str::orderedUuid(),
                'folder_id' => $folder->id,
                'user_id' => $folder->assigned_manager_id,
                'is_manager' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_manager_id');
        });

        Schema::table('folder_assignments', function (Blueprint $table) {
            $table->dropColumn('pole');
        });
    }

    /**
     * Reverse the migrations : remet la colonne du responsable et le pôle (vide) des affectations.
     */
    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->foreignUuid('assigned_manager_id')->nullable()->after('organization_id')->constrained('users');
        });

        Schema::table('folder_assignments', function (Blueprint $table) {
            $table->string('pole')->nullable()->after('user_id');
        });

        DB::table('folder_assignments')->where('is_manager', true)->orderBy('id')->each(function (object $assignment): void {
            DB::table('folders')->where('id', $assignment->folder_id)->update(['assigned_manager_id' => $assignment->user_id]);
        });

        Schema::table('folder_assignments', function (Blueprint $table) {
            $table->dropColumn('is_manager');
        });
    }
};

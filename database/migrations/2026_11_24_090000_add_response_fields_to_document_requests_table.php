<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parcours d'une demande de pièces : description des pièces attendues,
 * réponse du client (reçue ou rejetée avec motif) puis clôture par le cabinet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->foreignUuid('responded_by')->nullable()->after('last_reminded_at')->constrained('client_users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable()->after('responded_by');
            $table->text('rejection_reason')->nullable()->after('responded_at');
            $table->foreignUuid('closed_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable()->after('closed_by');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responded_by');
            $table->dropConstrainedForeignId('closed_by');
            $table->dropColumn(['description', 'responded_at', 'rejection_reason', 'closed_at']);
        });
    }
};

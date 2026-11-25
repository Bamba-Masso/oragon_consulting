<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Les statuts « Partiellement reçue » et « Annulée » ne font plus partie du parcours
 * d'une demande de pièces (En attente → Reçue ou Rejetée → Clôturée).
 * Ils ne sont supprimés que s'ils ne sont utilisés par aucune demande.
 */
return new class extends Migration
{
    /**
     * @var array<string, array{name: string, color: string}>
     */
    private const UNUSED_STATUSES = [
        'partially_received' => ['name' => 'Partiellement reçue', 'color' => '#2563EB'],
        'cancelled' => ['name' => 'Annulée', 'color' => '#DC2626'],
    ];

    public function up(): void
    {
        $statusTypeId = $this->documentRequestStatusTypeId();

        if ($statusTypeId === null) {
            return;
        }

        DB::table('statuses')
            ->where('status_type_id', $statusTypeId)
            ->whereIn('code', array_keys(self::UNUSED_STATUSES))
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))
                ->from('document_requests')
                ->whereColumn('document_requests.status_id', 'statuses.id'))
            ->delete();
    }

    public function down(): void
    {
        $statusTypeId = $this->documentRequestStatusTypeId();

        if ($statusTypeId === null) {
            return;
        }

        foreach (self::UNUSED_STATUSES as $code => $status) {
            $exists = DB::table('statuses')->where('status_type_id', $statusTypeId)->where('code', $code)->exists();

            if (! $exists) {
                DB::table('statuses')->insert([
                    'id' => (string) Str::uuid(),
                    'status_type_id' => $statusTypeId,
                    'code' => $code,
                    'name' => $status['name'],
                    'color' => $status['color'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function documentRequestStatusTypeId(): ?string
    {
        return DB::table('status_types')->where('code', 'document_request')->value('id');
    }
};

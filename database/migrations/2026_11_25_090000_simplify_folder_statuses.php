<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Nouveau parcours d'un dossier : En attente → En cours ⇄ Suspendu → Terminé.
 * Les anciens statuts sont regroupés sur les nouveaux (dossiers et historique compris), puis supprimés.
 */
return new class extends Migration
{
    /**
     * @var array<string, array{name: string, color: string}>
     */
    private const NEW_STATUSES = [
        'pending' => ['name' => 'En attente', 'color' => '#D97706'],
        'in_progress' => ['name' => 'En cours', 'color' => '#2563EB'],
        'suspended' => ['name' => 'Suspendu', 'color' => '#DC2626'],
        'closed' => ['name' => 'Terminé', 'color' => '#16A34A'],
    ];

    /**
     * Ancien statut => nouveau statut.
     *
     * @var array<string, string>
     */
    private const MAPPING = [
        'to_qualify' => 'pending',
        'awaiting_client' => 'pending',
        'to_process' => 'pending',
        'in_review' => 'in_progress',
        'to_validate' => 'in_progress',
        'published' => 'in_progress',
    ];

    /**
     * @var array<string, array{name: string, color: string}>
     */
    private const OLD_STATUSES = [
        'to_qualify' => ['name' => 'À qualifier', 'color' => '#6B7280'],
        'awaiting_client' => ['name' => 'En attente du client', 'color' => '#D97706'],
        'to_process' => ['name' => 'À traiter', 'color' => '#0891B2'],
        'in_review' => ['name' => 'En revue', 'color' => '#7C3AED'],
        'to_validate' => ['name' => 'À valider', 'color' => '#DB2777'],
        'published' => ['name' => 'Publié au client', 'color' => '#16A34A'],
    ];

    public function up(): void
    {
        $statusTypeId = $this->folderStatusTypeId();

        if ($statusTypeId === null) {
            return;
        }

        DB::transaction(function () use ($statusTypeId): void {
            foreach (self::NEW_STATUSES as $code => $status) {
                $this->upsertStatus($statusTypeId, $code, $status);
            }

            $ids = DB::table('statuses')->where('status_type_id', $statusTypeId)->pluck('id', 'code');

            foreach (self::MAPPING as $oldCode => $newCode) {
                if (! isset($ids[$oldCode])) {
                    continue;
                }

                DB::table('folders')->where('status_id', $ids[$oldCode])->update(['status_id' => $ids[$newCode]]);
                DB::table('folder_status_changes')->where('from_status_id', $ids[$oldCode])->update(['from_status_id' => $ids[$newCode]]);
                DB::table('folder_status_changes')->where('to_status_id', $ids[$oldCode])->update(['to_status_id' => $ids[$newCode]]);
            }

            DB::table('statuses')
                ->where('status_type_id', $statusTypeId)
                ->whereIn('code', array_keys(self::MAPPING))
                ->delete();

            // Un dossier terminé est toujours daté : c'est ce qui bloque les actions.
            DB::table('folders')
                ->where('status_id', $ids['closed'])
                ->whereNull('closed_at')
                ->update(['closed_at' => DB::raw('updated_at')]);
        });
    }

    /**
     * Les anciens statuts sont recréés ; les dossiers restent sur les nouveaux.
     */
    public function down(): void
    {
        $statusTypeId = $this->folderStatusTypeId();

        if ($statusTypeId === null) {
            return;
        }

        foreach (self::OLD_STATUSES as $code => $status) {
            $this->upsertStatus($statusTypeId, $code, $status);
        }

        $this->upsertStatus($statusTypeId, 'closed', ['name' => 'Clôturé', 'color' => '#111827']);
    }

    /**
     * @param  array{name: string, color: string}  $status
     */
    private function upsertStatus(string $statusTypeId, string $code, array $status): void
    {
        $existing = DB::table('statuses')->where('status_type_id', $statusTypeId)->where('code', $code);

        if ($existing->exists()) {
            $existing->update(['name' => $status['name'], 'color' => $status['color'], 'updated_at' => now()]);

            return;
        }

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

    private function folderStatusTypeId(): ?string
    {
        return DB::table('status_types')->where('code', 'folder')->value('id');
    }
};

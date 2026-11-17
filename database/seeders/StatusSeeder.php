<?php

namespace Database\Seeders;

use App\Models\StatusType;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statusTypes = [
            StatusType::FOLDER => [
                'name' => 'Dossier',
                'statuses' => [
                    ['code' => 'to_qualify', 'name' => 'À qualifier', 'color' => '#6B7280'],
                    ['code' => 'awaiting_client', 'name' => 'En attente du client', 'color' => '#D97706'],
                    ['code' => 'to_process', 'name' => 'À traiter', 'color' => '#0891B2'],
                    ['code' => 'in_progress', 'name' => 'En cours', 'color' => '#2563EB'],
                    ['code' => 'in_review', 'name' => 'En revue', 'color' => '#7C3AED'],
                    ['code' => 'to_validate', 'name' => 'À valider', 'color' => '#DB2777'],
                    ['code' => 'published', 'name' => 'Publié au client', 'color' => '#16A34A'],
                    ['code' => 'closed', 'name' => 'Clôturé', 'color' => '#111827'],
                ],
            ],
            StatusType::TASK => [
                'name' => 'Tâche',
                'statuses' => [
                    ['code' => 'pending', 'name' => 'À faire', 'color' => '#6B7280'],
                    ['code' => 'in_progress', 'name' => 'En cours', 'color' => '#2563EB'],
                    ['code' => 'completed', 'name' => 'Terminée', 'color' => '#16A34A'],
                    ['code' => 'cancelled', 'name' => 'Annulée', 'color' => '#DC2626'],
                ],
            ],
            StatusType::DOCUMENT_REQUEST => [
                'name' => 'Demande de document',
                'statuses' => [
                    ['code' => 'pending', 'name' => 'En attente', 'color' => '#D97706'],
                    ['code' => 'partially_received', 'name' => 'Partiellement reçue', 'color' => '#2563EB'],
                    ['code' => 'received', 'name' => 'Reçue', 'color' => '#16A34A'],
                    ['code' => 'cancelled', 'name' => 'Annulée', 'color' => '#DC2626'],
                ],
            ],
            StatusType::ANOMALY => [
                'name' => 'Anomalie',
                'statuses' => [
                    ['code' => 'open', 'name' => 'Ouverte', 'color' => '#DC2626'],
                    ['code' => 'in_progress', 'name' => 'En traitement', 'color' => '#D97706'],
                    ['code' => 'resolved', 'name' => 'Résolue', 'color' => '#16A34A'],
                    ['code' => 'closed', 'name' => 'Clôturée', 'color' => '#111827'],
                ],
            ],
        ];

        foreach ($statusTypes as $code => $definition) {
            $statusType = StatusType::updateOrCreate(
                ['code' => $code],
                ['name' => $definition['name']],
            );

            foreach ($definition['statuses'] as $status) {
                $statusType->statuses()->updateOrCreate(
                    ['code' => $status['code']],
                    ['name' => $status['name'], 'color' => $status['color']],
                );
            }
        }
    }
}

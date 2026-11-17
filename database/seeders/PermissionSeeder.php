<?php

namespace Database\Seeders;

use App\Models\ClientUser;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Catalogue des permissions, nommées « {action}-{module} ».
 */
class PermissionSeeder extends Seeder
{
    /**
     * Actions générées pour chaque module du cabinet.
     *
     * @var list<string>
     */
    private const BASE_ACTIONS = ['view', 'create', 'update', 'delete', 'restore', 'print', 'printList', 'lock', 'unlock'];

    /**
     * Modules du cabinet et leurs actions spécifiques (en plus de BASE_ACTIONS).
     *
     * @var array<string, list<string>>
     */
    private const CABINET_MODULES = [
        'organizations' => [],
        'client-users' => [],
        'users' => [],
        'roles' => [],
        'folders' => ['review', 'approve', 'publish'],
        'tasks' => ['assign', 'complete'],
        'documents' => ['upload', 'download', 'publish'],
        'document-requests' => ['remind', 'respond'],
        'anomalies' => ['review', 'resolve'],
        'dashboards' => ['export'],
        'audit' => ['export'],
    ];

    /**
     * Modules du portail client et leurs actions, listées explicitement :
     * un client n'a jamais besoin des actions d'administration.
     *
     * @var array<string, list<string>>
     */
    private const CLIENT_MODULES = [
        'portal' => ['view'],
        'folders' => ['view'],
        'tasks' => ['view'],
        'documents' => ['view', 'download', 'upload'],
        'document-requests' => ['view', 'respond'],
        'comments' => ['view', 'create'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::CABINET_MODULES as $module => $extraActions) {
            foreach ([...self::BASE_ACTIONS, ...$extraActions] as $action) {
                Permission::findOrCreate("{$action}-{$module}", User::GUARD);
            }
        }

        foreach (self::CLIENT_MODULES as $module => $actions) {
            foreach ($actions as $action) {
                Permission::findOrCreate("{$action}-{$module}", ClientUser::GUARD);
            }
        }
    }
}

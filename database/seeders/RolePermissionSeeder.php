<?php

namespace Database\Seeders;

use App\Models\ClientUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Matrice rôles × permissions. Chaque exécution resynchronise les rôles
 * sur cette matrice : c'est la référence des droits par défaut.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
         * DatabaseSeeder désactive les événements de modèle : le cache Spatie n'a donc
         * pas été invalidé à la création des permissions par PermissionSeeder.
         */
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::cabinetMatrix() as $roleName => $modules) {
            Role::findByName($roleName, User::GUARD)
                ->syncPermissions($this->expand($modules));
        }

        foreach (self::clientMatrix() as $roleName => $modules) {
            Role::findByName($roleName, ClientUser::GUARD)
                ->syncPermissions($this->expand($modules));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    public static function cabinetMatrix(): array
    {
        return [
            'secretaire' => [
                'organizations' => ['view'],
                'client-users' => ['view', 'create', 'update'],
                'folders' => ['view', 'create', 'update'],
                'tasks' => ['view', 'update'],
                'documents' => ['view', 'create', 'update', 'download', 'upload'],
                'document-requests' => ['view', 'create', 'update', 'remind'],
            ],
            'comptable' => [
                'folders' => ['view', 'create', 'update', 'review'],
                'tasks' => ['view', 'update', 'complete'],
                'documents' => ['view', 'download', 'upload'],
                'anomalies' => ['view', 'create', 'update'],
            ],
            'fiscaliste' => [
                'folders' => ['view', 'create', 'update', 'review'],
                'tasks' => ['view', 'update', 'complete'],
                'documents' => ['view', 'download', 'upload'],
                'anomalies' => ['view', 'create', 'update'],
            ],
            'financier' => [
                'folders' => ['view', 'create', 'update', 'review'],
                'tasks' => ['view', 'update', 'complete'],
                'documents' => ['view', 'download', 'upload'],
                'anomalies' => ['view', 'create', 'update'],
            ],
            'auditeur' => [
                'organizations' => ['view'],
                'client-users' => ['view'],
                'folders' => ['view', 'review', 'print', 'printList'],
                'tasks' => ['view'],
                'documents' => ['view', 'download', 'print', 'printList'],
                'anomalies' => ['view', 'create', 'update', 'review', 'resolve'],
                'audit' => ['view', 'print', 'printList', 'export'],
            ],
            'manager' => [
                'organizations' => ['view'],
                'client-users' => ['view', 'create', 'update'],
                'folders' => ['view', 'create', 'update', 'assign', 'review', 'approve', 'publish'],
                'tasks' => ['view', 'create', 'update', 'assign', 'complete'],
                'documents' => ['view', 'create', 'update', 'download', 'upload', 'publish'],
                'document-requests' => ['view', 'create', 'update', 'remind', 'respond'],
                'anomalies' => ['view', 'create', 'update', 'review', 'resolve'],
                'dashboards' => ['view', 'print', 'printList', 'export'],
            ],
            'direction' => [
                'organizations' => ['view'],
                'client-users' => ['view'],
                'users' => ['view', 'create', 'update'],
                'folders' => ['view', 'viewAll', 'review', 'approve', 'publish', 'print', 'printList'],
                'tasks' => ['view'],
                'documents' => ['view', 'download', 'publish', 'print', 'printList'],
                'anomalies' => ['view', 'review'],
                'dashboards' => ['view', 'print', 'printList', 'export'],
                'audit' => ['view', 'print', 'printList', 'export'],
            ],
            'administrateur' => [
                'organizations' => ['view', 'create', 'update', 'delete', 'restore', 'lock', 'unlock'],
                'client-users' => ['view', 'create', 'update', 'delete', 'restore', 'lock', 'unlock'],
                'users' => ['view', 'create', 'update', 'delete', 'restore', 'lock', 'unlock'],
                'roles' => ['view', 'create', 'update', 'delete', 'restore', 'lock', 'unlock'],
                'folders' => ['view', 'create', 'update', 'delete', 'restore', 'assign', 'review', 'approve', 'publish', 'lock', 'unlock'],
                'tasks' => ['view', 'create', 'update', 'delete', 'restore', 'assign', 'complete', 'lock', 'unlock'],
                'documents' => ['view', 'create', 'update', 'delete', 'restore', 'download', 'upload', 'publish', 'lock', 'unlock'],
                'document-requests' => ['view', 'create', 'update', 'delete', 'restore', 'remind', 'respond'],
                'anomalies' => ['view', 'create', 'update', 'delete', 'restore', 'review', 'resolve'],
                'dashboards' => ['view', 'print', 'printList', 'export'],
                'audit' => ['view', 'print', 'printList', 'export'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    public static function clientMatrix(): array
    {
        return [
            'client' => [
                'portal' => ['view'],
                'folders' => ['view'],
                'documents' => ['view', 'download'],
                'document-requests' => ['view', 'respond'],
                'comments' => ['view', 'create'],
            ],
        ];
    }

    /**
     * Transforme ['folders' => ['view']] en ['view-folders'].
     *
     * @param  array<string, list<string>>  $modules
     * @return list<string>
     */
    private function expand(array $modules): array
    {
        $permissions = [];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissions[] = "{$action}-{$module}";
            }
        }

        return $permissions;
    }
}

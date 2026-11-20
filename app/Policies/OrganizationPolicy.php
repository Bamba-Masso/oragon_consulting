<?php

namespace App\Policies;

use App\Models\ClientUser;
use App\Models\Organization;
use App\Models\User;

/**
 * Gestion des organisations clientes, réservée aux collaborateurs du cabinet.
 * Les fiches d'identité des organisations sont visibles par tout collaborateur ayant
 * « view-organizations » ; le contenu des dossiers reste filtré par FolderPolicy.
 */
class OrganizationPolicy
{
    public function viewAny(User|ClientUser $user): bool
    {
        return $this->cabinetCan($user, 'view-organizations');
    }

    public function view(User|ClientUser $user, Organization $organization): bool
    {
        return $this->cabinetCan($user, 'view-organizations');
    }

    public function create(User|ClientUser $user): bool
    {
        return $this->cabinetCan($user, 'create-organizations');
    }

    public function update(User|ClientUser $user, Organization $organization): bool
    {
        return $this->cabinetCan($user, 'update-organizations');
    }

    /**
     * Désactiver l'organisation : ses utilisateurs ne peuvent plus se connecter au portail.
     */
    public function lock(User|ClientUser $user, Organization $organization): bool
    {
        return $organization->is_active && $this->cabinetCan($user, 'lock-organizations');
    }

    /**
     * Réactiver l'organisation.
     */
    public function unlock(User|ClientUser $user, Organization $organization): bool
    {
        return ! $organization->is_active && $this->cabinetCan($user, 'unlock-organizations');
    }

    public function delete(User|ClientUser $user, Organization $organization): bool
    {
        return $this->cabinetCan($user, 'delete-organizations');
    }

    public function restore(User|ClientUser $user, Organization $organization): bool
    {
        return $this->cabinetCan($user, 'restore-organizations');
    }

    /**
     * Les organisations ne sont jamais supprimées définitivement (conservation de l'historique).
     */
    public function forceDelete(User|ClientUser $user, Organization $organization): bool
    {
        return false;
    }

    private function cabinetCan(User|ClientUser $user, string $permission): bool
    {
        return $user instanceof User && $user->can($permission);
    }
}

<?php

namespace App\Policies;

use App\Models\ClientUser;
use App\Models\User;

/**
 * Gestion des utilisateurs du portail client, réservée aux collaborateurs du cabinet.
 */
class ClientUserPolicy
{
    public function viewAny(User|ClientUser $user): bool
    {
        return $this->cabinetCan($user, 'view-client-users');
    }

    public function view(User|ClientUser $user, ClientUser $clientUser): bool
    {
        return $this->cabinetCan($user, 'view-client-users');
    }

    public function create(User|ClientUser $user): bool
    {
        return $this->cabinetCan($user, 'create-client-users');
    }

    public function update(User|ClientUser $user, ClientUser $clientUser): bool
    {
        return $this->cabinetCan($user, 'update-client-users');
    }

    /**
     * Désactiver l'accès : l'utilisateur ne peut plus se connecter au portail.
     */
    public function lock(User|ClientUser $user, ClientUser $clientUser): bool
    {
        return $clientUser->is_active && $this->cabinetCan($user, 'lock-client-users');
    }

    /**
     * Réactiver l'accès.
     */
    public function unlock(User|ClientUser $user, ClientUser $clientUser): bool
    {
        return ! $clientUser->is_active && $this->cabinetCan($user, 'unlock-client-users');
    }

    public function delete(User|ClientUser $user, ClientUser $clientUser): bool
    {
        return $this->cabinetCan($user, 'delete-client-users');
    }

    public function restore(User|ClientUser $user, ClientUser $clientUser): bool
    {
        return $this->cabinetCan($user, 'restore-client-users');
    }

    public function forceDelete(User|ClientUser $user, ClientUser $clientUser): bool
    {
        return false;
    }

    private function cabinetCan(User|ClientUser $user, string $permission): bool
    {
        return $user instanceof User && $user->can($permission);
    }
}

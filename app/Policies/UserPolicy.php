<?php

namespace App\Policies;

use App\Models\ClientUser;
use App\Models\Role;
use App\Models\User;

/**
 * Gestion des collaborateurs du cabinet, réservée aux collaborateurs habilités
 * (matrice des rôles : administrateur et direction).
 */
class UserPolicy
{
    /**
     * Rôle qu'un collaborateur ne peut attribuer que s'il gère lui-même les rôles.
     */
    private const ADMINISTRATOR_ROLE = 'administrateur';

    public function viewAny(User|ClientUser $user): bool
    {
        return $this->cabinetCan($user, 'view-users');
    }

    public function view(User|ClientUser $user, User $collaborator): bool
    {
        return $this->cabinetCan($user, 'view-users');
    }

    public function create(User|ClientUser $user): bool
    {
        return $this->cabinetCan($user, 'create-users');
    }

    /**
     * Le compte d'un administrateur n'est modifiable que par un collaborateur qui gère les rôles.
     */
    public function update(User|ClientUser $user, User $collaborator): bool
    {
        if (! $this->cabinetCan($user, 'update-users')) {
            return false;
        }

        return ! $collaborator->hasRole(self::ADMINISTRATOR_ROLE) || $user->can('update-roles');
    }

    /**
     * Attribuer un rôle : le rôle « administrateur » est réservé à ceux qui gèrent les rôles.
     */
    public function assignRole(User|ClientUser $user, string $role): bool
    {
        if (! array_key_exists($role, Role::CABINET_LABELS)) {
            return false;
        }

        return $role !== self::ADMINISTRATOR_ROLE || $this->cabinetCan($user, 'update-roles');
    }

    /**
     * Désactiver un compte : jamais le sien.
     */
    public function lock(User|ClientUser $user, User $collaborator): bool
    {
        return $collaborator->is_active
            && ! $collaborator->is($user)
            && $this->cabinetCan($user, 'lock-users');
    }

    public function unlock(User|ClientUser $user, User $collaborator): bool
    {
        return ! $collaborator->is_active && $this->cabinetCan($user, 'unlock-users');
    }

    public function delete(User|ClientUser $user, User $collaborator): bool
    {
        return ! $collaborator->is($user) && $this->cabinetCan($user, 'delete-users');
    }

    public function restore(User|ClientUser $user, User $collaborator): bool
    {
        return $this->cabinetCan($user, 'restore-users');
    }

    public function forceDelete(User|ClientUser $user, User $collaborator): bool
    {
        return false;
    }

    private function cabinetCan(User|ClientUser $user, string $permission): bool
    {
        return $user instanceof User && $user->can($permission);
    }
}

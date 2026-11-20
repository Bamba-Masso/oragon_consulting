<?php

namespace App\Policies;

use App\Models\ClientUser;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Autorisations sur les dossiers, en deux contrôles successifs :
 * 1. le périmètre (Folder::isVisibleTo) : hors périmètre, on répond 404 pour ne pas
 *    révéler l'existence du dossier, même si l'identifiant est modifié dans l'URL ;
 * 2. la permission du rôle (Spatie) : sans elle, on répond 403.
 */
class FolderPolicy
{
    /**
     * Accès à la liste des dossiers (à filtrer avec Folder::visibleTo()).
     */
    public function viewAny(User|ClientUser $user): bool
    {
        return $user->can('view-folders');
    }

    public function view(User|ClientUser $user, Folder $folder): Response
    {
        return $this->onFolder($user, $folder, 'view-folders');
    }

    public function create(User|ClientUser $user): bool
    {
        return $user instanceof User && $user->can('create-folders');
    }

    public function update(User|ClientUser $user, Folder $folder): Response
    {
        return $this->cabinetActionOnFolder($user, $folder, 'update-folders');
    }

    /**
     * Affecter ou retirer des collaborateurs du dossier.
     */
    public function assign(User|ClientUser $user, Folder $folder): Response
    {
        return $this->cabinetActionOnFolder($user, $folder, 'assign-folders');
    }

    /**
     * Passer le dossier en revue.
     */
    public function review(User|ClientUser $user, Folder $folder): Response
    {
        return $this->cabinetActionOnFolder($user, $folder, 'review-folders');
    }

    /**
     * Valider les livrables du dossier.
     */
    public function approve(User|ClientUser $user, Folder $folder): Response
    {
        return $this->cabinetActionOnFolder($user, $folder, 'approve-folders');
    }

    /**
     * Publier le dossier au client.
     */
    public function publish(User|ClientUser $user, Folder $folder): Response
    {
        return $this->cabinetActionOnFolder($user, $folder, 'publish-folders');
    }

    public function delete(User|ClientUser $user, Folder $folder): Response
    {
        return $this->cabinetActionOnFolder($user, $folder, 'delete-folders');
    }

    public function restore(User|ClientUser $user, Folder $folder): Response
    {
        return $this->cabinetActionOnFolder($user, $folder, 'restore-folders');
    }

    /**
     * Les dossiers ne sont jamais supprimés définitivement (conservation de l'historique).
     */
    public function forceDelete(User|ClientUser $user, Folder $folder): bool
    {
        return false;
    }

    /**
     * Action réservée aux collaborateurs du cabinet, même si un client voit le dossier.
     */
    private function cabinetActionOnFolder(User|ClientUser $user, Folder $folder, string $permission): Response
    {
        $response = $this->onFolder($user, $folder, $permission);

        if ($response->allowed() && $user instanceof ClientUser) {
            return Response::deny('Action réservée au cabinet.');
        }

        return $response;
    }

    private function onFolder(User|ClientUser $user, Folder $folder, string $permission): Response
    {
        if (! $folder->isVisibleTo($user)) {
            return Response::denyAsNotFound();
        }

        return $user->can($permission)
            ? Response::allow()
            : Response::deny('Vous n\'avez pas l\'autorisation d\'effectuer cette action.');
    }
}

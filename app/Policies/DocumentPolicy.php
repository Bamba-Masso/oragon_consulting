<?php

namespace App\Policies;

use App\Models\ClientUser;
use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Autorisations sur les documents d'un dossier.
 * - Le cabinet ajoute les documents, les envoie au client (publication), les retire ou les supprime.
 * - Le client ne voit et ne télécharge que les documents envoyés : un document interne lui répond 404.
 * Hors périmètre (dossier non visible), on répond 404 ; sans permission, 403.
 */
class DocumentPolicy
{
    public function viewAny(User|ClientUser $user): bool
    {
        return $user->can('view-documents');
    }

    /**
     * Ajouter un document au dossier (cabinet seulement).
     */
    public function create(User|ClientUser $user, Folder $folder): Response
    {
        if (! $folder->isVisibleTo($user)) {
            return Response::denyAsNotFound();
        }

        if (! $user instanceof User || ! $user->can('upload-documents')) {
            return $this->forbidden();
        }

        return $folder->isClosed() ? Response::deny(FolderPolicy::CLOSED_FOLDER_MESSAGE) : Response::allow();
    }

    public function view(User|ClientUser $user, Document $document): Response
    {
        if ($document->folder === null || ! $document->folder->isVisibleTo($user)) {
            return Response::denyAsNotFound();
        }

        if ($user instanceof ClientUser && ! $document->is_published_to_client) {
            return Response::denyAsNotFound();
        }

        return $user->can('view-documents') ? Response::allow() : $this->forbidden();
    }

    public function download(User|ClientUser $user, Document $document): Response
    {
        return $this->cabinetOrClient($user, $document, 'download-documents');
    }

    /**
     * Envoyer au client un document encore interne.
     * Reste possible une fois le dossier terminé : un document a pu être oublié au moment de la clôture.
     */
    public function publish(User|ClientUser $user, Document $document): Response
    {
        $response = $this->cabinetOnly($user, $document, 'publish-documents', evenIfFolderIsClosed: true);

        if ($response->allowed() && $document->is_published_to_client) {
            return Response::deny('Ce document a déjà été envoyé au client.');
        }

        return $response;
    }

    /**
     * Retirer un document envoyé par erreur : il redevient interne.
     */
    public function unpublish(User|ClientUser $user, Document $document): Response
    {
        $response = $this->cabinetOnly($user, $document, 'publish-documents');

        if ($response->allowed() && ! $document->is_published_to_client) {
            return Response::deny('Ce document n\'a pas été envoyé au client.');
        }

        return $response;
    }

    public function delete(User|ClientUser $user, Document $document): Response
    {
        return $this->cabinetOnly($user, $document, 'delete-documents');
    }

    /**
     * Action du cabinet sur le document : impossible une fois le dossier terminé, sauf exception explicite.
     */
    private function cabinetOnly(User|ClientUser $user, Document $document, string $permission, bool $evenIfFolderIsClosed = false): Response
    {
        $response = $this->cabinetOrClient($user, $document, $permission);

        if ($response->allowed() && $user instanceof ClientUser) {
            return $this->forbidden();
        }

        if ($response->allowed() && ! $evenIfFolderIsClosed && $document->folder->isClosed()) {
            return Response::deny(FolderPolicy::CLOSED_FOLDER_MESSAGE);
        }

        return $response;
    }

    private function cabinetOrClient(User|ClientUser $user, Document $document, string $permission): Response
    {
        $response = $this->view($user, $document);

        if ($response->denied()) {
            return $response;
        }

        return $user->can($permission) ? Response::allow() : $this->forbidden();
    }

    private function forbidden(): Response
    {
        return Response::deny('Vous n\'avez pas l\'autorisation d\'effectuer cette action.');
    }
}

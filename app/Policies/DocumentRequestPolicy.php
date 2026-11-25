<?php

namespace App\Policies;

use App\Models\ClientUser;
use App\Models\DocumentRequest;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Autorisations sur les demandes de pièces, selon le parcours :
 * - En attente : le client la marque reçue ou la rejette ; le cabinet peut relancer ;
 * - Reçue : discussion entre le cabinet et le client ; le cabinet peut clôturer ;
 * - Rejetée : plus aucune action, et le client ne peut plus en voir le détail ;
 * - Clôturée : consultation seule.
 * Hors périmètre (dossier non visible), on répond 404 ; sans permission ou au mauvais statut, 403.
 */
class DocumentRequestPolicy
{
    public function viewAny(User|ClientUser $user): bool
    {
        return $user->can('view-document-requests');
    }

    /**
     * Demander des pièces sur un dossier (cabinet seulement).
     */
    public function create(User|ClientUser $user, Folder $folder): Response
    {
        if (! $folder->isVisibleTo($user)) {
            return Response::denyAsNotFound();
        }

        if (! $user instanceof User || ! $user->can('create-document-requests')) {
            return $this->forbidden();
        }

        return $folder->isClosed() ? Response::deny(FolderPolicy::CLOSED_FOLDER_MESSAGE) : Response::allow();
    }

    public function view(User|ClientUser $user, DocumentRequest $documentRequest): Response
    {
        $response = $this->onRequest($user, $documentRequest, 'view-document-requests');

        if ($response->allowed() && $user instanceof ClientUser && $documentRequest->hasStatus(DocumentRequest::REJECTED)) {
            return Response::deny('Cette demande a été rejetée : son détail n\'est plus consultable.');
        }

        return $response;
    }

    /**
     * Marquer la demande reçue ou la rejeter (client, tant qu'elle est en attente).
     */
    public function respond(User|ClientUser $user, DocumentRequest $documentRequest): Response
    {
        if ($user instanceof User) {
            return $this->forbidden();
        }

        return $this->atStatus($user, $documentRequest, 'respond-document-requests', DocumentRequest::PENDING);
    }

    /**
     * Écrire dans la discussion (cabinet ou client), une fois la demande reçue.
     */
    public function sendMessage(User|ClientUser $user, DocumentRequest $documentRequest): Response
    {
        return $this->atStatus($user, $documentRequest, 'respond-document-requests', DocumentRequest::RECEIVED);
    }

    /**
     * Relancer le client (cabinet, tant que la demande est en attente).
     */
    public function remind(User|ClientUser $user, DocumentRequest $documentRequest): Response
    {
        if ($user instanceof ClientUser) {
            return $this->forbidden();
        }

        return $this->atStatus($user, $documentRequest, 'remind-document-requests', DocumentRequest::PENDING);
    }

    /**
     * Clôturer la demande à la fin de la discussion (cabinet).
     */
    public function close(User|ClientUser $user, DocumentRequest $documentRequest): Response
    {
        if ($user instanceof ClientUser) {
            return $this->forbidden();
        }

        return $this->atStatus($user, $documentRequest, 'update-document-requests', DocumentRequest::RECEIVED);
    }

    private function atStatus(User|ClientUser $user, DocumentRequest $documentRequest, string $permission, string $status): Response
    {
        $response = $this->view($user, $documentRequest);

        if ($response->denied()) {
            return $response;
        }

        if (! $user->can($permission)) {
            return $this->forbidden();
        }

        if ($documentRequest->folder->isClosed()) {
            return Response::deny(FolderPolicy::CLOSED_FOLDER_MESSAGE);
        }

        return $documentRequest->hasStatus($status)
            ? Response::allow()
            : Response::deny('Cette action n\'est pas possible au statut actuel de la demande.');
    }

    private function onRequest(User|ClientUser $user, DocumentRequest $documentRequest, string $permission): Response
    {
        if ($documentRequest->folder === null || ! $documentRequest->folder->isVisibleTo($user)) {
            return Response::denyAsNotFound();
        }

        return $user->can($permission) ? Response::allow() : $this->forbidden();
    }

    private function forbidden(): Response
    {
        return Response::deny('Vous n\'avez pas l\'autorisation d\'effectuer cette action.');
    }
}

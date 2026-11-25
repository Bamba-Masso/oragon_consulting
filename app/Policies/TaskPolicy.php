<?php

namespace App\Policies;

use App\Models\ClientUser;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Autorisations sur les tâches vues hors de la fiche du dossier (page « Mes tâches »).
 * Les actions sur une tâche reprennent les règles du dossier (FolderPolicy) : 404 hors périmètre, 403 sans permission.
 */
class TaskPolicy
{
    private const ADMINISTRATOR_ROLE = 'administrateur';

    /**
     * Accès à la page « Mes tâches ».
     */
    public function viewAny(User|ClientUser $user): bool
    {
        return $user instanceof User && $user->can('view-tasks');
    }

    /**
     * Détail d'une tâche : son dossier doit être dans le périmètre du collaborateur.
     */
    public function view(User|ClientUser $user, Task $task): Response
    {
        if ($task->folder === null || ! $task->folder->isVisibleTo($user)) {
            return Response::denyAsNotFound();
        }

        return $this->viewAny($user)
            ? Response::allow()
            : Response::deny('Vous n\'avez pas l\'autorisation d\'effectuer cette action.');
    }

    /**
     * Commenter une tâche : il suffit de pouvoir la consulter, tant que le dossier n'est pas terminé.
     */
    public function comment(User|ClientUser $user, Task $task): Response
    {
        $response = $this->view($user, $task);

        if ($response->allowed() && $task->folder->isClosed()) {
            return Response::deny(FolderPolicy::CLOSED_FOLDER_MESSAGE);
        }

        return $response;
    }

    /**
     * Démarrer une tâche en attente.
     */
    public function start(User|ClientUser $user, Task $task): Response
    {
        return $this->statusChange($user, $task, [Task::PENDING]);
    }

    /**
     * Terminer une tâche en cours : c'est définitif.
     */
    public function complete(User|ClientUser $user, Task $task): Response
    {
        return $this->statusChange($user, $task, [Task::IN_PROGRESS]);
    }

    /**
     * Annuler une tâche qui n'est pas encore terminée : c'est définitif.
     */
    public function cancel(User|ClientUser $user, Task $task): Response
    {
        return $this->statusChange($user, $task, [Task::PENDING, Task::IN_PROGRESS]);
    }

    /**
     * Ajouter, cocher ou supprimer une sous-tâche : il faut pouvoir modifier la tâche dans son dossier.
     */
    public function manageChecklist(User|ClientUser $user, Task $task): Response
    {
        if ($task->folder === null) {
            return Response::denyAsNotFound();
        }

        return Gate::forUser($user)->inspect('updateTask', $task->folder);
    }

    /**
     * Changer le statut : réservé à la personne en charge de la tâche et à l'administrateur,
     * seulement depuis les statuts attendus et tant que le dossier n'est pas terminé.
     * La personne en charge voit toujours le dossier de sa tâche : inutile de le vérifier pour elle.
     *
     * @param  list<string>  $expectedStatuses
     */
    private function statusChange(User|ClientUser $user, Task $task, array $expectedStatuses): Response
    {
        if ($task->folder === null) {
            return Response::denyAsNotFound();
        }

        $isAssignee = $user instanceof User && $task->assigned_to === $user->id;

        if (! $isAssignee) {
            if (! $task->folder->isVisibleTo($user)) {
                return Response::denyAsNotFound();
            }

            if (! $user instanceof User || ! $user->hasRole(self::ADMINISTRATOR_ROLE)) {
                return Response::deny('Seuls la personne en charge de la tâche et l\'administrateur peuvent changer son statut.');
            }
        }

        if ($task->folder->isClosed()) {
            return Response::deny(FolderPolicy::CLOSED_FOLDER_MESSAGE);
        }

        return $task->hasStatus(...$expectedStatuses)
            ? Response::allow()
            : Response::deny('Ce changement n\'est pas possible au statut actuel de la tâche.');
    }
}

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
     * Commenter une tâche : il suffit de pouvoir la consulter.
     */
    public function comment(User|ClientUser $user, Task $task): Response
    {
        return $this->view($user, $task);
    }

    /**
     * Ajouter, cocher ou supprimer un point de la checklist : il faut pouvoir modifier la tâche dans son dossier.
     */
    public function manageChecklist(User|ClientUser $user, Task $task): Response
    {
        if ($task->folder === null) {
            return Response::denyAsNotFound();
        }

        return Gate::forUser($user)->inspect('updateTask', $task->folder);
    }
}

<?php

namespace App\Policies;

use App\Models\ClientUser;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Un commentaire ne peut être modifié ou supprimé que par son auteur,
 * qui doit toujours pouvoir consulter la tâche (sinon 404).
 */
class TaskCommentPolicy
{
    public function update(User|ClientUser $user, TaskComment $comment): Response
    {
        return $this->byAuthor($user, $comment);
    }

    public function delete(User|ClientUser $user, TaskComment $comment): Response
    {
        return $this->byAuthor($user, $comment);
    }

    private function byAuthor(User|ClientUser $user, TaskComment $comment): Response
    {
        if ($comment->task === null) {
            return Response::denyAsNotFound();
        }

        $response = Gate::forUser($user)->inspect('view', $comment->task);

        if ($response->denied()) {
            return $response;
        }

        if ($comment->task->folder->isClosed()) {
            return Response::deny(FolderPolicy::CLOSED_FOLDER_MESSAGE);
        }

        return $user instanceof User && $comment->created_by === $user->id
            ? Response::allow()
            : Response::deny('Seul l\'auteur du commentaire peut le modifier ou le supprimer.');
    }
}

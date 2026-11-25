<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Parcours d'une tâche depuis la liste « Mes tâches » : En attente → En cours → Terminée, ou Annulée.
 * Réservé à la personne en charge de la tâche et à l'administrateur.
 */
class TaskStatusController extends Controller
{
    public function start(Task $task): RedirectResponse
    {
        Gate::authorize('start', $task);

        $this->moveTo($task, Task::IN_PROGRESS);

        return back()->with('status', "La tâche « {$task->title} » est en cours.");
    }

    public function complete(Task $task): RedirectResponse
    {
        Gate::authorize('complete', $task);

        $this->moveTo($task, Task::COMPLETED);

        return back()->with('status', "La tâche « {$task->title} » est terminée.");
    }

    public function cancel(Task $task): RedirectResponse
    {
        Gate::authorize('cancel', $task);

        $this->moveTo($task, Task::CANCELLED);

        return back()->with('status', "La tâche « {$task->title} » a été annulée.");
    }

    private function moveTo(Task $task, string $statusCode): void
    {
        $task->status_id = Task::statusId($statusCode);
        $task->completed_at = $statusCode === Task::COMPLETED ? now() : null;
        $task->save();
    }
}

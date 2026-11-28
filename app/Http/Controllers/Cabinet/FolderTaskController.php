<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Folder;
use App\Models\Status;
use App\Models\StatusType;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Tâches d'un dossier, gérées depuis sa fiche.
 */
class FolderTaskController extends Controller
{
    public function store(StoreTaskRequest $request, Folder $folder): RedirectResponse
    {
        $task = new Task($request->validated());
        $task->status_id = Status::ofType(StatusType::TASK)->where('code', Task::INITIAL_STATUS)->value('statuses.id');
        $task->created_by = $request->user()->id;
        $task->updated_by = $request->user()->id;

        $folder->tasks()->save($task);

        $this->notifyAssignee($task, $folder, $request->user());

        return to_route('cabinet.folders.show', $folder)
            ->with('status', "La tâche « {$task->title} » a été ajoutée au dossier.");
    }

    /**
     * Le formulaire est une modale de la fiche du dossier (vue cabinet.folder.partials.task-edit).
     * La tâche appartient forcément au dossier (liaison de route imbriquée).
     */
    public function update(UpdateTaskRequest $request, Folder $folder, Task $task): RedirectResponse
    {
        $task->fill($request->validated());
        $task->updated_by = $request->user()->id;
        $task->save();

        if ($task->wasChanged('assigned_to')) {
            $this->notifyAssignee($task, $folder, $request->user());
        }

        return to_route('cabinet.folders.show', $folder)
            ->with('status', "La tâche « {$task->title} » a été mise à jour.");
    }

    /**
     * Suppression logique : la tâche est conservée en base.
     */
    public function destroy(Folder $folder, Task $task): RedirectResponse
    {
        Gate::authorize('deleteTask', $folder);

        $task->delete();

        return to_route('cabinet.folders.show', $folder)
            ->with('status', "La tâche « {$task->title} » a été supprimée.");
    }

    /**
     * Prévient la personne à qui la tâche vient d'être assignée (sauf si elle se l'est assignée elle-même).
     */
    private function notifyAssignee(Task $task, Folder $folder, User $author): void
    {
        if ($task->assigned_to === null) {
            return;
        }

        ActivityNotification::sendTo(
            [User::find($task->assigned_to)],
            "{$author->full_name} vous a assigné la tâche « {$task->title} » du dossier {$folder->reference}.",
            route('folder.tasks.show', $task),
            $author,
        );
    }
}

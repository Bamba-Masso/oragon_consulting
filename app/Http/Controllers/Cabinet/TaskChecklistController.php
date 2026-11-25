<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Checklist d'une tâche, gérée depuis le détail de la tâche.
 */
class TaskChecklistController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        Gate::authorize('manageChecklist', $task);

        $validated = $request->validateWithBag('storeChecklist', [
            'label' => ['required', 'string', 'max:255'],
        ]);

        $task->checklistItems()->create([
            'label' => $validated['label'],
            'order' => (int) $task->checklistItems()->max('order') + 1,
        ]);

        return to_route('folder.tasks.show', $task)
            ->with('status', 'Le point a été ajouté à la checklist.');
    }

    /**
     * Coche le point s'il ne l'est pas, le décoche sinon.
     */
    public function toggleCompletion(Request $request, Task $task, TaskChecklist $checklistItem): RedirectResponse
    {
        Gate::authorize('manageChecklist', $task);

        $checklistItem->is_completed = ! $checklistItem->is_completed;
        $checklistItem->completed_by = $checklistItem->is_completed ? $request->user()->id : null;
        $checklistItem->save();

        return to_route('folder.tasks.show', $task);
    }

    public function destroy(Task $task, TaskChecklist $checklistItem): RedirectResponse
    {
        Gate::authorize('manageChecklist', $task);

        $checklistItem->delete();

        return to_route('folder.tasks.show', $task)
            ->with('status', "Le point « {$checklistItem->label} » a été supprimé de la checklist.");
    }
}

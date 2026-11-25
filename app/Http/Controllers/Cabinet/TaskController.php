<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TaskController extends Controller
{
   
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Task::class);

        $search = $request->string('search')->trim()->toString();

        $tasks = Task::query()
            ->where('assigned_to', $request->user()->id)
            ->whereHas('folder')
            ->with(['status', 'priority'])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereAny(['title', 'description'], 'like', "%{$search}%")
                ->orWhereHas('folder', fn (Builder $query) => $query->whereAny(['reference', 'title'], 'like', "%{$search}%"))
                ->orWhereRelation('folder.organization', 'name', 'like', "%{$search}%")))
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('cabinet.task.index', [
            'tasks' => $tasks,
            'search' => $search,
        ]);
    }

    /**
     * Détail d'une tâche : informations, checklist et commentaires.
     */
    public function show(Task $task): View
    {
        Gate::authorize('view', $task);

        $task->load([
            'folder.organization',
            'status',
            'priority',
            'assignee',
            'checklistItems',
            'comments' => fn ($query) => $query->with('author')->latest(),
        ]);

        // Les droits sur chaque commentaire passent par sa tâche : inutile de la recharger.
        $task->comments->each->setRelation('task', $task);

        return view('cabinet.task.show', ['task' => $task]);
    }
}

<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskComment;
use App\Notifications\ActivityNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Commentaires internes d'une tâche, gérés depuis le détail de la tâche.
 */
class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        Gate::authorize('comment', $task);

        $validated = $request->validateWithBag('storeComment', $this->rules(), $this->messages());

        $comment = new TaskComment(['content' => $validated['content']]);
        $comment->created_by = $request->user()->id;
        $task->comments()->save($comment);

        ActivityNotification::sendTo(
            [$task->assignee],
            "{$request->user()->full_name} a commenté la tâche « {$task->title} ».",
            route('folder.tasks.show', $task),
            $request->user(),
        );

        return to_route('folder.tasks.show', $task)
            ->with('status', 'Le commentaire a été ajouté.');
    }

    /**
     * Le formulaire est une modale du détail de la tâche (une par commentaire de l'auteur).
     */
    public function update(Request $request, Task $task, TaskComment $comment): RedirectResponse
    {
        Gate::authorize('update', $comment);

        $comment->update($request->validateWithBag('updateComment', $this->rules(), $this->messages()));

        return to_route('folder.tasks.show', $task)
            ->with('status', 'Le commentaire a été modifié.');
    }

    public function destroy(Task $task, TaskComment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return to_route('folder.tasks.show', $task)
            ->with('status', 'Le commentaire a été supprimé.');
    }

    /**
     * @return array<string, list<string>>
     */
    private function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'content.required' => 'Écrivez votre commentaire.',
            'content.max' => 'Le commentaire ne doit pas dépasser :max caractères.',
        ];
    }
}

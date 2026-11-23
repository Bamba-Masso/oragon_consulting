{{--
    Modale de modification d'une tâche (incluse sur la fiche du dossier, une par tâche).
    Attend $folder (équipe chargée), $task et $priorities. Erreurs dans le sac « updateTask » ;
    ancienne saisie et ouverture automatique seulement pour la tâche soumise (champs cachés form et task_id).
    La personne en charge n'est modifiable qu'avec le droit d'assigner les tâches.
--}}
@php
    $modalId = 'edit-task-'.$task->id;
    $isSubmitted = old('form') === 'task-edit' && old('task_id') === $task->id;
    $field = fn (string $name): string => "{$modalId}-{$name}";
    $teamMembers = $folder->assignees->mapWithKeys(fn ($member) => [
        $member->id => $member->pole ? "{$member->full_name} — {$member->pole}" : $member->full_name,
    ]);
    $descriptionError = $isSubmitted ? $errors->updateTask->first('description') : null;
@endphp
<x-modal-form
    :id="$modalId"
    title="Modifier la tâche"
    :action="route('cabinet.folders.tasks.update', [$folder, $task])"
    method="PUT"
    submit-label="Enregistrer"
    max-width="max-w-2xl"
    :auto-open="$isSubmitted && $errors->updateTask->any()"
>
    <input type="hidden" name="form" value="task-edit">
    <input type="hidden" name="task_id" value="{{ $task->id }}">

    <x-form.input name="title" :id="$field('title')" label="Intitulé" :value="$task->title" bag="updateTask" :submitted="$isSubmitted" required autocomplete="off" />

    <div>
        <label for="{{ $field('description') }}" class="block mb-2 text-sm font-medium text-navy-900">Description</label>
        <textarea id="{{ $field('description') }}" name="description" rows="3"
            @class([
                'block w-full rounded-xl border bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-4 transition',
                'border-red-400 focus:border-red-500 focus:ring-red-500/10' => $descriptionError,
                'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15' => ! $descriptionError,
            ])>{{ $isSubmitted ? old('description') : $task->description }}</textarea>
        @if ($descriptionError)
            <p class="mt-2 text-xs text-red-600">{{ $descriptionError }}</p>
        @endif
    </div>

    @can('assignTask', $folder)
        <x-form.select name="assigned_to" :id="$field('assigned_to')" label="Personne en charge" :options="$teamMembers" :value="$task->assigned_to"
            bag="updateTask" :submitted="$isSubmitted" placeholder="Non assignée" />
    @else
        <p class="text-sm text-slate-500">
            Personne en charge : <span class="font-medium text-navy-900">{{ $task->assignee?->full_name ?? 'non assignée' }}</span>
        </p>
    @endcan

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form.select name="priority_id" :id="$field('priority_id')" label="Priorité" :options="$priorities" :value="$task->priority_id"
            bag="updateTask" :submitted="$isSubmitted" required />
        <x-form.input type="date" name="due_date" :id="$field('due_date')" label="Échéance" :value="$task->due_date?->format('Y-m-d')"
            bag="updateTask" :submitted="$isSubmitted" />
    </div>
</x-modal-form>

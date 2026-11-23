{{--
    Modale d'ajout d'une tâche (incluse sur la fiche du dossier). Attend $folder (équipe chargée) et $priorities.
    La tâche est créée au statut « En cours » et assignée à un membre de l'équipe du dossier.
    Les erreurs sont dans le sac « storeTask ».
--}}
@php
    $teamMembers = $folder->assignees->mapWithKeys(fn ($member) => [
        $member->id => $member->pole ? "{$member->full_name} — {$member->pole}" : $member->full_name,
    ]);
    /* L'ancienne saisie n'est reprise que si c'est ce formulaire qui a été soumis (champ caché « form »). */
    $isSubmitted = old('form') === 'task';
@endphp
<x-modal-form
    id="create-task-modal"
    title="Nouvelle tâche"
    :action="route('cabinet.folders.tasks.store', $folder)"
    submit-label="Ajouter la tâche"
    max-width="max-w-2xl"
    :auto-open="$errors->storeTask->any()"
>
    <input type="hidden" name="form" value="task">

    <x-form.input name="title" id="task-title" label="Intitulé" bag="storeTask" :submitted="$isSubmitted" required autocomplete="off" placeholder="Ex. : Rapprochement bancaire de mars" />

    <div>
        <label for="task-description" class="block mb-2 text-sm font-medium text-navy-900">Description</label>
        <textarea id="task-description" name="description" rows="3"
            @class([
                'block w-full rounded-xl border bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-4 transition',
                'border-red-400 focus:border-red-500 focus:ring-red-500/10' => $errors->storeTask->has('description'),
                'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15' => ! $errors->storeTask->has('description'),
            ])
            placeholder="Facultatif : consignes, pièces à utiliser…">{{ $isSubmitted ? old('description') : '' }}</textarea>
        @error('description', 'storeTask')
            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <x-form.select name="assigned_to" id="task-assigned-to" label="Personne en charge" :options="$teamMembers" bag="storeTask"
        :submitted="$isSubmitted" placeholder="À assigner plus tard" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form.select name="priority_id" id="task-priority" label="Priorité" :options="$priorities"
            :value="$priorities->search('Normale') ?: null" bag="storeTask" :submitted="$isSubmitted" required />
        <x-form.input type="date" name="due_date" id="task-due-date" label="Échéance" bag="storeTask" :submitted="$isSubmitted" />
    </div>

   
</x-modal-form>

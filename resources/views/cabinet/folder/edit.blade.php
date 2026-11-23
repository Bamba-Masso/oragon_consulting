{{--
    Modale de modification d'un dossier (incluse sur la fiche).
    Attend $folder, $priorities, $collaborators et $missionTypes.
    L'entreprise n'est pas modifiable. Le statut non plus pour l'instant : il suivra le parcours du dossier, à définir.
    L'équipe et le responsable ne sont modifiables qu'avec le droit d'affecter (FolderPolicy::assign).
    Les erreurs sont dans le sac « updateFolder ». La fiche contient d'autres formulaires (tâches, personnes) :
    l'ancienne saisie n'est reprise que si c'est ce formulaire qui a été soumis (champ caché form=folder).
--}}
@php
    $isSubmitted = old('form') === 'folder';
@endphp
<x-modal-form
    id="edit-folder-modal"
    :title="'Modifier le dossier '.$folder->reference"
    :action="route('cabinet.folders.update', $folder)"
    method="PUT"
    submit-label="Enregistrer"
    max-width="max-w-2xl"
    :auto-open="$errors->updateFolder->any()"
>
    <input type="hidden" name="form" value="folder">

    <p class="text-sm text-slate-500">
        Entreprise : <span class="font-medium text-navy-900">{{ $folder->organization?->name ?? '—' }}</span>
    </p>

    <x-form.input name="title" id="edit-folder-title" label="Intitulé" :value="$folder->title" bag="updateFolder" :submitted="$isSubmitted" required autocomplete="off" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form.input name="mission_type" id="edit-folder-mission-type" label="Type de mission" :value="$folder->mission_type" list="edit-mission-types"
            bag="updateFolder" :submitted="$isSubmitted" required autocomplete="off" />
        <x-form.input name="period" id="edit-folder-period" label="Période" :value="$folder->period" bag="updateFolder" :submitted="$isSubmitted" autocomplete="off" />
        <x-form.select name="priority_id" id="edit-folder-priority" label="Priorité" :options="$priorities" :value="$folder->priority_id"
            bag="updateFolder" :submitted="$isSubmitted" required />
        <x-form.input type="date" name="due_date" id="edit-folder-due-date" label="Échéance" :value="$folder->due_date?->format('Y-m-d')"
            bag="updateFolder" :submitted="$isSubmitted" />
    </div>

    @can('assign', $folder)
        @include('cabinet.folder.partials.team-fields', [
            'managerId' => $folder->manager?->id,
            'memberIds' => $folder->assignees->pluck('id')->map(fn ($id) => (string) $id)->all(),
            'bag' => 'updateFolder',
            'idPrefix' => 'edit-folder-',
            'submitted' => $isSubmitted,
        ])
    @else
        <p class="rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
            Responsable : <span class="font-medium text-navy-900">{{ $folder->manager?->full_name ?? 'à désigner' }}</span>.
            Seul un responsable de dossier peut modifier l'équipe.
        </p>
    @endcan

    <datalist id="edit-mission-types">
        @foreach ($missionTypes as $missionType)
            <option value="{{ $missionType }}"></option>
        @endforeach
    </datalist>
</x-modal-form>

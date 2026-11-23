{{--
    Modale d'ajout d'une personne en charge (incluse sur la fiche du dossier).
    Attend $folder (équipe chargée) et $collaborators ([id => « Nom — Rôle »] des membres actifs du cabinet).
    Ne propose que les collaborateurs qui ne font pas déjà partie du dossier. Erreurs dans le sac « addMember ».
--}}
@php
    $available = $collaborators->except($folder->assignees->pluck('id')->all());
@endphp
<x-modal-form
    id="add-member-modal"
    title="Ajouter une personne en charge"
    :action="route('cabinet.folders.members.store', $folder)"
    submit-label="Ajouter"
    max-width="max-w-md"
    :auto-open="$errors->addMember->any()"
>
    @if ($available->isEmpty())
        <p class="text-sm text-slate-500">Tous les collaborateurs actifs font déjà partie du dossier.</p>
    @else
        <x-form.select name="user_id" id="add-member-user" label="Collaborateur" :options="$available" bag="addMember"
            placeholder="Choisir un collaborateur…" required />
    @endif
</x-modal-form>


<x-modal-form
    id="create-folder-modal"
    title="Nouveau dossier"
    :action="route('cabinet.folders.store')"
    submit-label="Ouvrir le dossier"
    max-width="max-w-2xl"
    :auto-open="$errors->any()"
>
    <x-form.select name="organization_id" label="Entreprise" :options="$activeOrganizations"
        :value="$organizationId ?? null" placeholder="Choisir une entreprise…" required />

    <x-form.input name="title" label="Intitulé" required autocomplete="off" placeholder="Ex. : Bilan annuel 2026" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form.input name="mission_type" label="Type de mission" list="mission-types" required autocomplete="off"
            hint="Choisissez dans la liste ou saisissez un autre type." />
        <x-form.input name="period" label="Période" autocomplete="off" placeholder="Ex. : 2026, T3 2026" />
        <x-form.select name="priority_id" label="Priorité" :options="$priorities"
            :value="$priorities->search('Normale') ?: null" placeholder="Choisir…" required />
        <x-form.input type="date" name="due_date" label="Échéance" />
    </div>

    @include('cabinet.folder.partials.team-fields', [
        'managerId' => null,
        'memberIds' => [],
        'bag' => 'default',
        'idPrefix' => 'create-folder-',
    ])

    <datalist id="mission-types">
        @foreach ($missionTypes as $missionType)
            <option value="{{ $missionType }}"></option>
        @endforeach
    </datalist>
</x-modal-form>

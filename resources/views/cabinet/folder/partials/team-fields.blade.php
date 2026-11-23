
@php
    $submitted ??= true;
    $checkedIds = $submitted && session()->hasOldInput() ? array_map('strval', (array) old('assignee_ids', [])) : $memberIds;
    $teamError = $errors->getBag($bag)->first('assignee_ids') ?: $errors->getBag($bag)->first('assignee_ids.*');
@endphp

<x-form.select name="manager_id" :id="$idPrefix.'manager'" label="Responsable du dossier" :options="$collaborators" :value="$managerId"
    :bag="$bag" :submitted="$submitted" placeholder="À désigner plus tard" />

<fieldset>
    <legend class="mb-2 text-sm font-medium text-navy-900">Équipe du dossier</legend>
    <div @class([
        'max-h-52 overflow-y-auto rounded-xl border bg-slate-50/50 divide-y divide-slate-100',
        'border-red-400' => $teamError,
        'border-slate-200' => ! $teamError,
    ])>
        @forelse ($collaborators as $collaboratorId => $collaboratorLabel)
            <label for="{{ $idPrefix }}member-{{ $collaboratorId }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 cursor-pointer hover:bg-white transition">
                <input type="checkbox" id="{{ $idPrefix }}member-{{ $collaboratorId }}" name="assignee_ids[]" value="{{ $collaboratorId }}"
                    @checked(in_array((string) $collaboratorId, $checkedIds, true))
                    class="w-4 h-4 rounded border-slate-300 text-navy-900 focus:ring-gold-500/30">
                <span class="truncate">{{ $collaboratorLabel }}</span>
            </label>
        @empty
            <p class="px-4 py-3 text-sm text-slate-500">Aucun collaborateur actif.</p>
        @endforelse
    </div>
    @if ($teamError)
        <p class="mt-2 text-xs text-red-600">{{ $teamError }}</p>
    @else
        <p class="mt-2 text-xs text-slate-400">Cochez les collaborateurs qui travaillent sur le dossier. Le responsable en fait partie d'office.</p>
    @endif
</fieldset>

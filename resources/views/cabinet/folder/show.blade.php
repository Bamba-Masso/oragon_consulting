<x-layouts.cabinet :title="$folder->reference">
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- Retour et actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <a href="{{ route('cabinet.folders.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-navy-900 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                Dossiers
            </a>

            <div class="flex flex-wrap gap-2">
                @can('update', $folder)
                    <button type="button" data-modal-target="edit-folder-modal" data-modal-toggle="edit-folder-modal"
                        class="inline-flex items-center px-4 py-2 rounded-xl bg-navy-900 hover:bg-navy-800 text-sm font-medium text-white transition">
                        Modifier
                    </button>
                @endcan

                @can('delete', $folder)
                    <button type="button" data-modal-target="delete-folder-modal" data-modal-toggle="delete-folder-modal"
                        class="inline-flex items-center px-4 py-2 rounded-xl border border-red-100 bg-white text-sm font-medium text-red-600 hover:bg-red-50 transition">
                        Supprimer
                    </button>
                @endcan
            </div>
        </div>

        <x-flash />

        {{-- En-tête et informations --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            <div class="bg-linear-to-r from-navy-900 to-navy-800 px-6 py-6 sm:px-8 text-white">
                <p class="font-mono text-xs text-white/60">{{ $folder->reference }}</p>
                <h2 class="mt-1 text-lg font-bold leading-tight break-words">{{ $folder->title }}</h2>
                <p class="mt-1 text-sm text-white/70">
                    {{ $folder->organization?->name ?? '—' }} · {{ $folder->mission_type }}@if ($folder->period) · {{ $folder->period }}@endif
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-status-badge :label="$folder->status->name" :color="$folder->status->color" on-dark />
                    @if ($folder->priority)
                        <span class="rounded-md bg-white/15 px-2 py-0.5 text-xs font-semibold">Priorité {{ mb_strtolower($folder->priority->label) }}</span>
                    @endif
                    @if ($folder->is_overdue)
                        <span class="rounded-md bg-red-500 px-2 py-0.5 text-xs font-semibold">En retard</span>
                    @endif
                </div>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5 px-6 py-6 sm:px-8 text-sm">
                <div class="min-w-0">
                    <dt class="text-xs text-slate-400">Entreprise</dt>
                    <dd class="mt-1 font-medium text-navy-900">
                        @if ($folder->organization)
                            @can('view', $folder->organization)
                                <a href="{{ route('cabinet.organizations.show', $folder->organization) }}" class="hover:text-gold-700 transition">{{ $folder->organization->name }}</a>
                            @else
                                {{ $folder->organization->name }}
                            @endcan
                        @else
                            —
                        @endif
                    </dd>
                </div>

                @foreach ([
                    'Responsable' => $folder->manager?->full_name,
                    'Échéance' => $folder->due_date?->format('d/m/Y'),
                    'Ouvert le' => $folder->created_at->format('d/m/Y').($folder->creator ? ' par '.$folder->creator->full_name : ''),
                    'Clôturé le' => $folder->closed_at?->format('d/m/Y à H:i'),
                ] as $label => $value)
                    <div class="min-w-0">
                        <dt class="text-xs text-slate-400">{{ $label }}</dt>
                        <dd class="mt-1 font-medium text-navy-900 break-words">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Tâches et personnes en charge : l'un sous l'autre, en pleine largeur ; côte à côte seulement sur très grand écran --}}
        <div class="grid grid-cols-1 2xl:grid-cols-3 gap-6">

            {{-- Tâches --}}
            <section class="2xl:col-span-2 bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
                <header class="flex items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                    <h3 class="text-base font-semibold text-navy-900">Tâches</h3>
                    @can('addTask', $folder)
                        <button type="button" data-modal-target="create-task-modal" data-modal-toggle="create-task-modal"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-navy-900 hover:bg-navy-800 text-xs font-semibold text-white transition">
                            <svg class="w-3.5 h-3.5 text-gold-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Nouvelle tâche
                        </button>
                    @endcan
                </header>

                @forelse ($folder->tasks as $task)
                    <div class="flex items-center gap-4 px-6 py-3 border-b border-slate-100 last:border-b-0 text-sm">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-navy-900 truncate">{{ $task->title }}</p>
                            <p class="text-xs text-slate-500 truncate">
                                @if ($task->assignee)
                                    {{ $task->assignee->full_name }}@if ($task->assignee->pole) · {{ $task->assignee->pole }}@endif
                                @else
                                    Non assignée
                                @endif
                                @if ($task->due_date)
                                    · <span @class(['font-semibold text-red-600' => $task->due_date->isPast() && ! $task->completed_at])>échéance {{ $task->due_date->format('d/m/Y') }}</span>
                                @endif
                            </p>
                        </div>
                        <x-status-badge :label="$task->status->name" :color="$task->status->color" />

                        @canany(['updateTask', 'deleteTask'], $folder)
                            <div class="flex items-center gap-0.5 shrink-0">
                                @can('updateTask', $folder)
                                    <button type="button" data-modal-target="edit-task-{{ $task->id }}" data-modal-toggle="edit-task-{{ $task->id }}"
                                        title="Modifier" aria-label="Modifier la tâche {{ $task->title }}"
                                        class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-navy-900 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                    </button>
                                @endcan
                                @can('deleteTask', $folder)
                                    <button type="button" data-modal-target="delete-task-{{ $task->id }}" data-modal-toggle="delete-task-{{ $task->id }}"
                                        title="Supprimer" aria-label="Supprimer la tâche {{ $task->title }}"
                                        class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                @endcan
                            </div>
                        @endcanany
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-slate-500">Aucune tâche pour le moment.</p>
                @endforelse
            </section>

            {{-- Personnes en charge du dossier : le responsable d'abord, puis le reste de l'équipe --}}
            <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
                <header class="flex items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                    <h3 class="text-base font-semibold text-navy-900">Personnes en charge du dossier</h3>
                    @can('assign', $folder)
                        <button type="button" data-modal-target="add-member-modal" data-modal-toggle="add-member-modal"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-navy-900 hover:bg-navy-800 text-xs font-semibold text-white transition">
                            <svg class="w-3.5 h-3.5 text-gold-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Ajouter
                        </button>
                    @endcan
                </header>

                @forelse ($folder->assignees->sortByDesc(fn ($member) => $member->pivot->is_manager) as $member)
                    <div class="flex items-center gap-3 px-6 py-3 border-b border-slate-100 last:border-b-0">
                        <span @class([
                            'w-8 h-8 shrink-0 flex items-center justify-center rounded-full text-xs font-semibold',
                            'bg-gold-500/15 text-gold-700' => $member->pivot->is_manager,
                            'bg-navy-900 text-gold-500' => ! $member->pivot->is_manager,
                        ])>{{ $member->initials }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-navy-900 truncate">{{ $member->full_name }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $member->pivot->is_manager ? 'Responsable du dossier' : ($member->pole ?? $member->role_label ?? 'Collaborateur') }}
                            </p>
                        </div>
                        @can('assign', $folder)
                            <button type="button" data-modal-target="remove-member-{{ $member->id }}" data-modal-toggle="remove-member-{{ $member->id }}"
                                title="Retirer du dossier" aria-label="Retirer {{ $member->full_name }} du dossier"
                                class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        @endcan
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-slate-500">Aucune personne en charge.</p>
                @endforelse
            </section>
        </div>

        {{-- Historique des statuts --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            <header class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-base font-semibold text-navy-900">Historique des statuts</h3>
            </header>

            @if ($folder->statusChanges->isEmpty())
                <p class="px-6 py-10 text-center text-sm text-slate-500">Aucun changement de statut enregistré.</p>
            @else
                <ol class="px-6 py-5 space-y-5">
                    @foreach ($folder->statusChanges as $change)
                        <li class="relative flex gap-4">
                            <span class="mt-1.5 w-2.5 h-2.5 shrink-0 rounded-full" style="background-color: {{ $change->toStatus->color }}"></span>
                            <div class="min-w-0 text-sm">
                                <p class="text-navy-900">
                                    @if ($change->fromStatus)
                                        {{ $change->fromStatus->name }} → <span class="font-semibold">{{ $change->toStatus->name }}</span>
                                    @else
                                        <span class="font-semibold">{{ $change->toStatus->name }}</span>
                                    @endif
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ $change->created_at->format('d/m/Y à H:i') }} · {{ $change->author?->full_name ?? 'Système' }}
                                    @if ($change->comment) · {{ $change->comment }}@endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>

    @can('addTask', $folder)
        @include('cabinet.folder.partials.task-create')
    @endcan

    {{-- Modales des tâches : modification et confirmation de suppression --}}
    @foreach ($folder->tasks as $task)
        @can('updateTask', $folder)
            @include('cabinet.folder.partials.task-edit', ['task' => $task])
        @endcan
        @can('deleteTask', $folder)
            <x-confirm-modal
                :id="'delete-task-'.$task->id"
                title="Supprimer la tâche ?"
                :action="route('cabinet.folders.tasks.destroy', [$folder, $task])"
                method="DELETE"
                confirm-label="Supprimer"
            >
                La tâche <strong class="font-semibold text-navy-900">{{ $task->title }}</strong> sera retirée du dossier.
            </x-confirm-modal>
        @endcan
    @endforeach

    {{-- Modales des personnes en charge : ajout et confirmation de retrait --}}
    @can('assign', $folder)
        @include('cabinet.folder.partials.member-add')

        @foreach ($folder->assignees as $member)
            <x-confirm-modal
                :id="'remove-member-'.$member->id"
                title="Retirer du dossier ?"
                :action="route('cabinet.folders.members.destroy', [$folder, $member])"
                method="DELETE"
                confirm-label="Retirer"
            >
                <strong class="font-semibold text-navy-900">{{ $member->full_name }}</strong> ne fera plus partie des personnes en charge du dossier.
                @if ($member->pivot->is_manager)
                    Le dossier n'aura plus de responsable.
                @endif
                Ses tâches dans ce dossier ne seront plus assignées.
            </x-confirm-modal>
        @endforeach
    @endcan

    @can('update', $folder)
        @include('cabinet.folder.edit')
    @endcan

    @can('delete', $folder)
        <x-confirm-modal
            id="delete-folder-modal"
            title="Supprimer le dossier ?"
            :action="route('cabinet.folders.destroy', $folder)"
            method="DELETE"
            confirm-label="Supprimer"
        >
            Le dossier <strong class="font-semibold text-navy-900">{{ $folder->reference }}</strong> sera retiré de la liste.
            Son historique est conservé.
        </x-confirm-modal>
    @endcan
</x-layouts.cabinet>

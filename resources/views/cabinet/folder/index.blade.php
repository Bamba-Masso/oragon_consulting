<x-layouts.cabinet title="Dossiers">
    @php
        $isFiltered = $search !== '' || $statusId !== '' || $priorityId !== '' || $organizationId !== '';
        $selectClass = 'rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition';
    @endphp

    <div class="space-y-6">

        <x-flash />

        {{-- Ouverture d'un dossier --}}
        @can('create', App\Models\Folder::class)
            <div class="flex justify-end">
                <button data-modal-target="create-folder-modal" data-modal-toggle="create-folder-modal" type="button"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold transition focus:outline-none focus:ring-4 focus:ring-gold-500/25">
                    <svg class="w-4 h-4 text-gold-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Nouveau dossier
                </button>
            </div>
            @include('cabinet.folder.create')
        @endcan

        {{-- Recherche et filtres, sur toute la largeur --}}
        <form method="GET" action="{{ route('cabinet.folders.index') }}" class="flex flex-col lg:flex-row gap-3 w-full">
            <label for="search" class="sr-only">Rechercher</label>
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Référence, intitulé, mission, entreprise…"
                    class="block w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition">
            </div>

            <label for="organization" class="sr-only">Entreprise</label>
            <select id="organization" name="organization" class="lg:w-56 {{ $selectClass }}">
                <option value="">Toutes les entreprises</option>
                @foreach ($organizations as $id => $name)
                    <option value="{{ $id }}" @selected($organizationId === $id)>{{ $name }}</option>
                @endforeach
            </select>

            <label for="status" class="sr-only">Statut</label>
            <select id="status" name="status" class="{{ $selectClass }}">
                <option value="">Tous les statuts</option>
                @foreach ($statuses as $id => $name)
                    <option value="{{ $id }}" @selected($statusId === $id)>{{ $name }}</option>
                @endforeach
            </select>

            <label for="priority" class="sr-only">Priorité</label>
            <select id="priority" name="priority" class="{{ $selectClass }}">
                <option value="">Toutes les priorités</option>
                @foreach ($priorities as $id => $label)
                    <option value="{{ $id }}" @selected($priorityId === $id)>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium text-navy-900 hover:bg-slate-50 transition">
                Filtrer
            </button>
        </form>

        {{-- Liste en cartes --}}
        <div>
            @if ($folders->isEmpty())
                <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 px-6 py-16 text-center">
                    <svg class="mx-auto w-10 h-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/></svg>
                    <p class="mt-3 text-sm font-medium text-navy-900">Aucun dossier</p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $isFiltered ? 'Modifiez votre recherche ou vos filtres.' : 'Les dossiers qui vous sont confiés apparaîtront ici.' }}
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($folders as $folder)
                        <a href="{{ route('cabinet.folders.show', $folder) }}"
                            class="group flex flex-col overflow-hidden bg-white rounded-2xl ring-1 ring-slate-200/70 hover:ring-gold-500/50 transition focus:outline-none focus:ring-2 focus:ring-gold-500/50">
                            <span class="h-1.5 bg-navy-900" aria-hidden="true"></span>
                            <div class="p-5 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="flex items-center gap-2 text-xs text-slate-400">
                                        <span class="font-mono">{{ $folder->reference }}</span>
                                        @if ($folder->priority)
                                            <span class="inline-flex items-center gap-1 font-medium" style="color: {{ $folder->priority->color }}">
                                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $folder->priority->color }}"></span>
                                                {{ $folder->priority->label }}
                                            </span>
                                        @endif
                                    </p>
                                    <x-status-badge :label="$folder->status->name" :color="$folder->status->color" />
                                </div>

                                <p class="mt-2 font-semibold text-navy-900 truncate group-hover:text-gold-700 transition">{{ $folder->title }}</p>
                                <p class="mt-0.5 text-sm text-slate-600 truncate">{{ $folder->organization?->name ?? '—' }}</p>
                                <p class="mt-0.5 text-xs text-slate-500 truncate">{{ $folder->mission_type }}@if ($folder->period) · {{ $folder->period }}@endif</p>
                            </div>

                            <div class="flex items-center justify-between gap-4 px-5 py-3 border-t border-slate-100 text-xs text-slate-500">
                                <span class="truncate">{{ $folder->manager?->full_name ?? 'Responsable à désigner' }}</span>
                                @if ($folder->due_date)
                                    <span @class(['shrink-0', 'font-semibold text-red-600' => $folder->is_overdue])>
                                        Échéance {{ $folder->due_date->format('d/m/Y') }}
                                    </span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                @if ($folders->hasPages())
                    <nav class="flex items-center justify-between gap-4 pt-6 text-sm" aria-label="Pagination">
                        <p class="text-slate-500">Page {{ $folders->currentPage() }} sur {{ $folders->lastPage() }}</p>
                        <div class="flex gap-2">
                            @if ($folders->onFirstPage())
                                <span class="px-3 py-1.5 rounded-lg border border-slate-100 text-slate-300">Précédente</span>
                            @else
                                <a href="{{ $folders->previousPageUrl() }}" rel="prev" class="px-3 py-1.5 rounded-lg border border-slate-200 text-navy-900 hover:bg-slate-50 transition">Précédente</a>
                            @endif
                            @if ($folders->hasMorePages())
                                <a href="{{ $folders->nextPageUrl() }}" rel="next" class="px-3 py-1.5 rounded-lg border border-slate-200 text-navy-900 hover:bg-slate-50 transition">Suivante</a>
                            @else
                                <span class="px-3 py-1.5 rounded-lg border border-slate-100 text-slate-300">Suivante</span>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        </div>
    </div>
</x-layouts.cabinet>

<x-layouts.cabinet title="Organisations">
    <div class="space-y-6">

        <x-flash />

        {{-- Ajout --}}
        @can('create', App\Models\Organization::class)
            <div class="flex justify-end">
                <button type="button" data-modal-target="create-organization-modal" data-modal-toggle="create-organization-modal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold transition focus:outline-none focus:ring-4 focus:ring-gold-500/25">
                    <svg class="w-4 h-4 text-gold-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Nouvelle organisation
                </button>
            </div>
            @include('cabinet.organizations.create')
        @endcan

        {{-- Recherche et filtre, sur toute la largeur --}}
        <form method="GET" action="{{ route('cabinet.organizations.index') }}" class="flex flex-col sm:flex-row gap-3 w-full">
            <label for="search" class="sr-only">Rechercher</label>
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Raison sociale, RCCM, NCC, e-mail, ville…"
                    class="block w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition">
            </div>

            <label for="status" class="sr-only">Statut</label>
            <select id="status" name="status"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition">
                <option value="">Tous les statuts</option>
                @foreach ($statusFilters as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium text-navy-900 hover:bg-slate-50 transition">
                Filtrer
            </button>
        </form>

        {{-- Liste en cartes --}}
        <div>
            @if ($organizations->isEmpty())
                <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 px-6 py-16 text-center">
                    <svg class="mx-auto w-10 h-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                    <p class="mt-3 text-sm font-medium text-navy-900">Aucune organisation</p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $search !== '' || $status !== '' ? 'Modifiez votre recherche ou vos filtres.' : 'Les organisations clientes apparaîtront ici.' }}
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($organizations as $organization)
                        <a href="{{ route('cabinet.organizations.show', $organization) }}"
                            class="group flex flex-col overflow-hidden bg-white rounded-2xl ring-1 ring-gold-500/50 shadow-md shadow-navy-900/5 hover:ring-gold-500 hover:shadow-lg hover:shadow-navy-900/10 transition focus:outline-none focus:ring-2 focus:ring-gold-500/50">
                            <span class="h-1.5 bg-navy-900" aria-hidden="true"></span>
                            <div class="p-5 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gold-700 truncate">{{ $organization->name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $organization->legal_form }} · {{ $organization->city }}</p>
                                    </div>
                                    <x-organization-status :active="$organization->is_active" />
                                </div>

                                <div class="mt-4 space-y-1 text-sm">
                                    <p class="text-slate-600 truncate">{{ $organization->email }}</p>
                                    <p class="text-slate-500">{{ $organization->phone }}</p>
                                    <p class="text-xs text-slate-400">RCCM {{ $organization->registration_number }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 px-5 py-3 border-t border-slate-100 text-xs text-slate-500">
                                <span><strong class="font-semibold text-navy-900">{{ $organization->client_users_count }}</strong> {{ $organization->client_users_count > 1 ? 'utilisateurs' : 'utilisateur' }}</span>
                                <span><strong class="font-semibold text-navy-900">{{ $organization->folders_count }}</strong> {{ $organization->folders_count > 1 ? 'dossiers' : 'dossier' }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                @if ($organizations->hasPages())
                    <nav class="flex items-center justify-between gap-4 pt-6 text-sm" aria-label="Pagination">
                        <p class="text-slate-500">Page {{ $organizations->currentPage() }} sur {{ $organizations->lastPage() }}</p>
                        <div class="flex gap-2">
                            @if ($organizations->onFirstPage())
                                <span class="px-3 py-1.5 rounded-lg border border-slate-100 text-slate-300">Précédente</span>
                            @else
                                <a href="{{ $organizations->previousPageUrl() }}" rel="prev" class="px-3 py-1.5 rounded-lg border border-slate-200 text-navy-900 hover:bg-slate-50 transition">Précédente</a>
                            @endif
                            @if ($organizations->hasMorePages())
                                <a href="{{ $organizations->nextPageUrl() }}" rel="next" class="px-3 py-1.5 rounded-lg border border-slate-200 text-navy-900 hover:bg-slate-50 transition">Suivante</a>
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

<x-layouts.cabinet title="Utilisateurs clients">
    @php
        $isFiltered = $search !== '' || $organizationId !== '' || $status !== '';
    @endphp

    <div class="space-y-6">

        <x-flash />

        {{-- Ajout --}}
        @can('create', App\Models\ClientUser::class)
            <div class="flex justify-end">
                <button data-modal-target="create-client-user-modal" data-modal-toggle="create-client-user-modal" type="button"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold transition focus:outline-none focus:ring-4 focus:ring-gold-500/25">
                    <svg class="w-4 h-4 text-gold-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Nouvel utilisateur
                </button>
            </div>
        @include('cabinet.client-users.create', ['selectedOrganizationId' => $organizationId])
        @endcan

        {{-- Recherche et filtres, sur toute la largeur --}}
        <form method="GET" action="{{ route('cabinet.client-users.index') }}" class="flex flex-col md:flex-row gap-3 w-full">
            <label for="search" class="sr-only">Rechercher</label>
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Nom, e-mail, nom d'utilisateur, organisation…"
                    class="block w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition">
            </div>

            <label for="organization" class="sr-only">Organisation</label>
            <select id="organization" name="organization"
                class="md:w-64 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition">
                <option value="">Toutes les organisations</option>
                @foreach ($organizations as $id => $name)
                    <option value="{{ $id }}" @selected($organizationId === $id)>{{ $name }}</option>
                @endforeach
            </select>

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

        {{-- Liste --}}
        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            @if ($clientUsers->isEmpty())
                <div class="px-6 py-16 text-center">
                    <svg class="mx-auto w-10 h-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    <p class="mt-3 text-sm font-medium text-navy-900">Aucun utilisateur</p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $isFiltered ? 'Modifiez votre recherche ou vos filtres.' : 'Les utilisateurs du portail client apparaîtront ici.' }}
                    </p>
                </div>
            @else
                {{-- Largeurs fixes et colonnes secondaires masquées sur petit écran : pas de défilement horizontal --}}
                <div>
                    <table class="w-full table-fixed text-sm">
                        <thead class="bg-slate-50 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-2.5">Utilisateur</th>
                                <th scope="col" class="hidden sm:table-cell w-1/5 px-3 py-2.5">Entreprise</th>
                                <th scope="col" class="hidden xl:table-cell w-1/6 px-3 py-2.5">Fonction</th>
                                <th scope="col" class="hidden lg:table-cell w-36 px-3 py-2.5">Dernière connexion</th>
                                <th scope="col" class="hidden md:table-cell w-28 px-3 py-2.5">Statut</th>
                                <th scope="col" class="w-32 px-5 py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($clientUsers as $clientUser)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            @if ($clientUser->avatar_url)
                                                <img src="{{ $clientUser->avatar_url }}" alt="" loading="lazy"
                                                    @class(['w-10 h-10 shrink-0 rounded-full object-cover ring-2 ring-white shadow-sm', 'grayscale opacity-60' => ! $clientUser->is_active])>
                                            @else
                                                <span @class([
                                                    'w-10 h-10 shrink-0 flex items-center justify-center rounded-full text-xs font-semibold',
                                                    'bg-navy-900 text-gold-500' => $clientUser->is_active,
                                                    'bg-slate-200 text-slate-500' => ! $clientUser->is_active,
                                                ])>{{ $clientUser->initials }}</span>
                                            @endif
                                            <div class="min-w-0">
                                                @can('view', $clientUser)
                                                    <a href="{{ route('cabinet.client-users.show', $clientUser) }}" class="block font-medium text-navy-900 truncate hover:text-gold-700 transition">{{ $clientUser->full_name }}</a>
                                                @else
                                                    <p class="font-medium text-navy-900 truncate">{{ $clientUser->full_name }}</p>
                                                @endcan
                                                <p class="text-xs text-slate-500 truncate">{{ $clientUser->email }}</p>
                                                @unless ($clientUser->is_active)
                                                    <p class="md:hidden text-xs font-medium text-slate-400">Désactivé</p>
                                                @endunless
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden sm:table-cell px-3 py-3">
                                        @if ($clientUser->organization)
                                            @can('view', $clientUser->organization)
                                                <a href="{{ route('cabinet.organizations.show', $clientUser->organization) }}" class="block truncate text-slate-700 hover:text-gold-700 transition" title="{{ $clientUser->organization->name }}">{{ $clientUser->organization->name }}</a>
                                            @else
                                                <span class="block truncate text-slate-700" title="{{ $clientUser->organization->name }}">{{ $clientUser->organization->name }}</span>
                                            @endcan
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="hidden xl:table-cell px-3 py-3 text-slate-600 truncate" title="{{ $clientUser->job_title }}">{{ $clientUser->job_title ?: '—' }}</td>
                                    <td class="hidden lg:table-cell px-3 py-3 text-xs text-slate-500">
                                        {{ $clientUser->last_login_at?->format('d/m/Y à H:i') ?? 'Jamais' }}
                                    </td>
                                    <td class="hidden md:table-cell px-3 py-3">
                                        <x-organization-status :active="$clientUser->is_active" active-label="Actif" inactive-label="Désactivé" />
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            @can('view', $clientUser)
                                                <a href="{{ route('cabinet.client-users.show', $clientUser) }}" title="Voir" aria-label="Voir {{ $clientUser->full_name }}"
                                                    class="p-2 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-navy-900 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                                </a>
                                            @endcan

                                            @can('update', $clientUser)
                                                <button type="button" data-modal-target="edit-client-user-{{ $clientUser->id }}" data-modal-toggle="edit-client-user-{{ $clientUser->id }}"
                                                    title="Modifier" aria-label="Modifier {{ $clientUser->full_name }}"
                                                    class="p-2 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-navy-900 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                                </button>
                                            @endcan

                                            @canany(['lock', 'unlock'], $clientUser)
                                                <button type="button" data-modal-target="activation-client-user-{{ $clientUser->id }}" data-modal-toggle="activation-client-user-{{ $clientUser->id }}"
                                                    title="{{ $clientUser->is_active ? 'Désactiver' : 'Réactiver' }}"
                                                    aria-label="{{ $clientUser->is_active ? 'Désactiver' : 'Réactiver' }} {{ $clientUser->full_name }}"
                                                    @class([
                                                        'p-2 rounded-lg transition',
                                                        'text-slate-400 hover:bg-red-50 hover:text-red-600' => $clientUser->is_active,
                                                        'text-slate-400 hover:bg-emerald-50 hover:text-emerald-600' => ! $clientUser->is_active,
                                                    ])>
                                                    @if ($clientUser->is_active)
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                                    @endif
                                                </button>
                                            @endcanany
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Modales de modification et de confirmation (hors du tableau : une div n'a pas sa place dans un tbody) --}}
                @foreach ($clientUsers as $clientUser)
                    @can('update', $clientUser)
                        @include('cabinet.client-users.edit', ['clientUser' => $clientUser])
                    @endcan
                    @canany(['lock', 'unlock'], $clientUser)
                        @include('cabinet.client-users.activation', ['clientUser' => $clientUser])
                    @endcanany
                @endforeach

                @if ($clientUsers->hasPages())
                    <nav class="flex items-center justify-between gap-4 px-6 py-4 border-t border-slate-100 text-sm" aria-label="Pagination">
                        <p class="text-slate-500">Page {{ $clientUsers->currentPage() }} sur {{ $clientUsers->lastPage() }}</p>
                        <div class="flex gap-2">
                            @if ($clientUsers->onFirstPage())
                                <span class="px-3 py-1.5 rounded-lg border border-slate-100 text-slate-300">Précédente</span>
                            @else
                                <a href="{{ $clientUsers->previousPageUrl() }}" rel="prev" class="px-3 py-1.5 rounded-lg border border-slate-200 text-navy-900 hover:bg-slate-50 transition">Précédente</a>
                            @endif
                            @if ($clientUsers->hasMorePages())
                                <a href="{{ $clientUsers->nextPageUrl() }}" rel="next" class="px-3 py-1.5 rounded-lg border border-slate-200 text-navy-900 hover:bg-slate-50 transition">Suivante</a>
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

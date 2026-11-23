<x-layouts.cabinet title="Collaborateurs">
    @php
        $isFiltered = $search !== '' || $role !== '' || $status !== '';
    @endphp

    <div class="space-y-6">

        <x-flash />

        {{-- Ajout --}}
        @can('create', App\Models\User::class)
            <div class="flex justify-end">
                <button data-modal-target="create-user-modal" data-modal-toggle="create-user-modal" type="button"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold transition focus:outline-none focus:ring-4 focus:ring-gold-500/25">
                    <svg class="w-4 h-4 text-gold-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Nouveau collaborateur
                </button>
            </div>
            @include('cabinet.users.create')
        @endcan

        {{-- Recherche et filtres, sur toute la largeur --}}
        <form method="GET" action="{{ route('cabinet.users.index') }}" class="flex flex-col md:flex-row gap-3 w-full">
            <label for="search" class="sr-only">Rechercher</label>
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Nom, e-mail, nom d'utilisateur…"
                    class="block w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition">
            </div>

            <label for="role" class="sr-only">Rôle</label>
            <select id="role" name="role"
                class="md:w-56 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition">
                <option value="">Tous les rôles</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
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
            @if ($collaborators->isEmpty())
                <div class="px-6 py-16 text-center">
                    <svg class="mx-auto w-10 h-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    <p class="mt-3 text-sm font-medium text-navy-900">Aucun collaborateur</p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $isFiltered ? 'Modifiez votre recherche ou vos filtres.' : 'Les collaborateurs du cabinet apparaîtront ici.' }}
                    </p>
                </div>
            @else
                {{-- Largeurs fixes et colonnes secondaires masquées sur petit écran : pas de défilement horizontal --}}
                <div>
                    <table class="w-full table-fixed text-sm">
                        <thead class="bg-slate-50 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-2.5">Collaborateur</th>
                                <th scope="col" class="hidden sm:table-cell w-1/5 px-3 py-2.5">Rôle</th>
                                <th scope="col" class="hidden xl:table-cell w-40 px-3 py-2.5">Téléphone</th>
                                <th scope="col" class="hidden lg:table-cell w-36 px-3 py-2.5">Dernière connexion</th>
                                <th scope="col" class="hidden md:table-cell w-28 px-3 py-2.5">Statut</th>
                                <th scope="col" class="w-32 px-5 py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($collaborators as $collaborator)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            @if ($collaborator->avatar_url)
                                                <img src="{{ $collaborator->avatar_url }}" alt="" loading="lazy"
                                                    @class(['w-10 h-10 shrink-0 rounded-full object-cover ring-2 ring-white shadow-sm', 'grayscale opacity-60' => ! $collaborator->is_active])>
                                            @else
                                                <span @class([
                                                    'w-10 h-10 shrink-0 flex items-center justify-center rounded-full text-xs font-semibold',
                                                    'bg-navy-900 text-gold-500' => $collaborator->is_active,
                                                    'bg-slate-200 text-slate-500' => ! $collaborator->is_active,
                                                ])>{{ $collaborator->initials }}</span>
                                            @endif
                                            <div class="min-w-0">
                                                @can('view', $collaborator)
                                                    <a href="{{ route('cabinet.users.show', $collaborator) }}" class="block font-medium text-navy-900 truncate hover:text-gold-700 transition">{{ $collaborator->full_name }}</a>
                                                @else
                                                    <p class="font-medium text-navy-900 truncate">{{ $collaborator->full_name }}</p>
                                                @endcan
                                                <p class="text-xs text-slate-500 truncate">{{ $collaborator->email }}</p>
                                                @unless ($collaborator->is_active)
                                                    <p class="md:hidden text-xs font-medium text-slate-400">Désactivé</p>
                                                @endunless
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden sm:table-cell px-3 py-3">
                                        @if ($collaborator->role_label)
                                            <span class="inline-block max-w-full truncate rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $collaborator->role_label }}</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="hidden xl:table-cell px-3 py-3 text-slate-600 truncate">{{ $collaborator->phone ?: '—' }}</td>
                                    <td class="hidden lg:table-cell px-3 py-3 text-xs text-slate-500">
                                        {{ $collaborator->last_login_at?->format('d/m/Y à H:i') ?? 'Jamais' }}
                                    </td>
                                    <td class="hidden md:table-cell px-3 py-3">
                                        <x-organization-status :active="$collaborator->is_active" active-label="Actif" inactive-label="Désactivé" />
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            @can('view', $collaborator)
                                                <a href="{{ route('cabinet.users.show', $collaborator) }}" title="Voir" aria-label="Voir {{ $collaborator->full_name }}"
                                                    class="p-2 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-navy-900 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                                </a>
                                            @endcan

                                            @can('update', $collaborator)
                                                <button type="button" data-modal-target="edit-user-{{ $collaborator->id }}" data-modal-toggle="edit-user-{{ $collaborator->id }}"
                                                    title="Modifier" aria-label="Modifier {{ $collaborator->full_name }}"
                                                    class="p-2 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-navy-900 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                                </button>
                                            @endcan

                                            @canany(['lock', 'unlock'], $collaborator)
                                                <button type="button" data-modal-target="activation-user-{{ $collaborator->id }}" data-modal-toggle="activation-user-{{ $collaborator->id }}"
                                                    title="{{ $collaborator->is_active ? 'Désactiver' : 'Réactiver' }}"
                                                    aria-label="{{ $collaborator->is_active ? 'Désactiver' : 'Réactiver' }} {{ $collaborator->full_name }}"
                                                    @class([
                                                        'p-2 rounded-lg transition',
                                                        'text-slate-400 hover:bg-red-50 hover:text-red-600' => $collaborator->is_active,
                                                        'text-slate-400 hover:bg-emerald-50 hover:text-emerald-600' => ! $collaborator->is_active,
                                                    ])>
                                                    @if ($collaborator->is_active)
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
                @foreach ($collaborators as $collaborator)
                    @can('update', $collaborator)
                        @include('cabinet.users.edit', ['collaborator' => $collaborator])
                    @endcan
                    @canany(['lock', 'unlock'], $collaborator)
                        @include('cabinet.users.activation', ['collaborator' => $collaborator])
                    @endcanany
                @endforeach

                @if ($collaborators->hasPages())
                    <nav class="flex items-center justify-between gap-4 px-6 py-4 border-t border-slate-100 text-sm" aria-label="Pagination">
                        <p class="text-slate-500">Page {{ $collaborators->currentPage() }} sur {{ $collaborators->lastPage() }}</p>
                        <div class="flex gap-2">
                            @if ($collaborators->onFirstPage())
                                <span class="px-3 py-1.5 rounded-lg border border-slate-100 text-slate-300">Précédente</span>
                            @else
                                <a href="{{ $collaborators->previousPageUrl() }}" rel="prev" class="px-3 py-1.5 rounded-lg border border-slate-200 text-navy-900 hover:bg-slate-50 transition">Précédente</a>
                            @endif
                            @if ($collaborators->hasMorePages())
                                <a href="{{ $collaborators->nextPageUrl() }}" rel="next" class="px-3 py-1.5 rounded-lg border border-slate-200 text-navy-900 hover:bg-slate-50 transition">Suivante</a>
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

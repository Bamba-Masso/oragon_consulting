<x-layouts.cabinet :title="$organization->name">
    @php
        $initialsOf = fn (string $name): string => collect(preg_split('/[\s\-]+/u', $name))
            ->filter(fn (string $word): bool => mb_strlen($word) > 2)
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->join('') ?: mb_strtoupper(mb_substr($name, 0, 2));
    @endphp

    <div class="max-w-6xl mx-auto space-y-6">

        {{-- Retour et actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <a href="{{ route('cabinet.organizations.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-navy-900 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                Organisations
            </a>

            <div class="flex flex-wrap gap-2">
                @can('update', $organization)
                    <a href="{{ route('cabinet.organizations.edit', $organization) }}"
                        class="inline-flex items-center px-4 py-2 rounded-xl bg-navy-900 hover:bg-navy-800 text-sm font-medium text-white transition">
                        Modifier
                    </a>
                @endcan

                @canany(['lock', 'unlock'], $organization)
                    <button type="button" data-modal-target="activation-organization-modal" data-modal-toggle="activation-organization-modal"
                        class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-navy-900 hover:bg-slate-50 transition">
                        {{ $organization->is_active ? 'Désactiver' : 'Réactiver' }}
                    </button>
                @endcanany

                @can('delete', $organization)
                    <button type="button" data-modal-target="delete-organization-modal" data-modal-toggle="delete-organization-modal"
                        class="inline-flex items-center px-4 py-2 rounded-xl border border-red-100 bg-white text-sm font-medium text-red-600 hover:bg-red-50 transition">
                        Supprimer
                    </button>
                @endcan
            </div>
        </div>

        <x-flash />

        {{-- En-tête de l'organisation --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            <div class="bg-linear-to-r from-navy-900 to-navy-800 px-6 py-6 sm:px-8 text-white">
                <div class="flex flex-col xl:flex-row xl:items-center gap-6 xl:gap-10">
                    {{-- Identité --}}
                    <div class="flex items-center gap-4 min-w-0 xl:w-72 shrink-0">
                        <span class="w-16 h-16 shrink-0 flex items-center justify-center rounded-full bg-white text-lg font-bold text-navy-900 shadow-lg shadow-black/20">
                            {{ $initialsOf($organization->name) }}
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold uppercase leading-tight break-words">{{ $organization->name }}</h2>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-md bg-white/15 px-2 py-0.5 text-xs font-semibold">{{ $organization->legal_form }}</span>
                                <x-organization-status :active="$organization->is_active" />
                            </div>
                        </div>
                    </div>

                    {{-- Informations --}}
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-5 flex-1 text-sm">
                        <div class="flex items-start gap-3 min-w-0">
                            <span class="w-11 h-11 shrink-0 flex items-center justify-center rounded-xl bg-white/10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <dt class="text-[11px] font-medium uppercase tracking-wider text-white/60">Contact</dt>
                                <dd class="mt-1 font-semibold">{{ $organization->phone ?: '—' }}</dd>
                                <dd class="text-white/70 break-all">{{ $organization->email }}</dd>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 min-w-0">
                            <span class="w-11 h-11 shrink-0 flex items-center justify-center rounded-xl bg-white/10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <dt class="text-[11px] font-medium uppercase tracking-wider text-white/60">Adresse</dt>
                                <dd class="mt-1 font-semibold">{{ $organization->city ?: '—' }}</dd>
                                <dd class="text-white/70">{{ $organization->address }}</dd>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 min-w-0">
                            <span class="w-11 h-11 shrink-0 flex items-center justify-center rounded-xl bg-white/10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <dt class="text-[11px] font-medium uppercase tracking-wider text-white/60">Identifiants</dt>
                                <dd class="mt-1 font-semibold break-all">RCCM {{ $organization->registration_number ?: '—' }}</dd>
                                <dd class="text-white/70 break-all">NCC {{ $organization->tax_number ?: '—' }}</dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Chiffres clés --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                @foreach ([
                    ['Dossiers', $folders->count() + $hiddenFoldersCount, 'bg-sky-50 text-sky-700', 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z'],
                    ['Utilisateurs du portail', $organization->clientUsers->count(), 'bg-emerald-50 text-emerald-700', 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
                ] as [$label, $value, $tint, $icon])
                    <div class="flex items-center gap-4 px-6 sm:px-8 py-5">
                        <span class="w-12 h-12 shrink-0 flex items-center justify-center rounded-xl {{ $tint }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        </span>
                        <div>
                            <p class="text-xl font-bold text-navy-900">{{ $value }}</p>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ $label }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Utilisateurs du portail --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            <header class="px-6 sm:px-8 py-5 border-b border-slate-100">
                <h3 class="text-base font-semibold text-navy-900">Utilisateurs du portail</h3>
                <p class="mt-1 text-sm text-slate-500">Personnes de l'organisation qui accèdent à l'espace client.</p>
            </header>

            @if ($organization->clientUsers->isEmpty())
                <p class="px-6 sm:px-8 py-10 text-center text-sm text-slate-500">Aucun utilisateur rattaché pour le moment.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                            <tr>
                                <th scope="col" class="px-6 sm:px-8 py-3">Nom</th>
                                <th scope="col" class="hidden md:table-cell px-4 py-3">Fonction</th>
                                <th scope="col" class="hidden lg:table-cell px-4 py-3">Téléphone</th>
                                <th scope="col" class="hidden sm:table-cell px-4 py-3">Dernière connexion</th>
                                <th scope="col" class="px-6 sm:px-8 py-3 text-right">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($organization->clientUsers as $clientUser)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="px-6 sm:px-8 py-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-9 h-9 shrink-0 flex items-center justify-center rounded-full bg-navy-900 text-xs font-semibold text-gold-500">
                                                {{ $initialsOf($clientUser->full_name) }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="font-medium text-navy-900 truncate">{{ $clientUser->full_name }}</p>
                                                <p class="text-xs text-slate-500 truncate">{{ $clientUser->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden md:table-cell px-4 py-3 text-slate-600">{{ $clientUser->job_title ?: '—' }}</td>
                                    <td class="hidden lg:table-cell px-4 py-3 text-slate-600 whitespace-nowrap">{{ $clientUser->phone ?: '—' }}</td>
                                    <td class="hidden sm:table-cell px-4 py-3 text-slate-500 whitespace-nowrap">
                                        {{ $clientUser->last_login_at?->format('d/m/Y à H:i') ?? 'Jamais connecté' }}
                                    </td>
                                    <td class="px-6 sm:px-8 py-3 text-right">
                                        <x-organization-status :active="$clientUser->is_active" active-label="Actif" inactive-label="Désactivé" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Dossiers --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            <header class="px-6 sm:px-8 py-5 border-b border-slate-100">
                <h3 class="text-base font-semibold text-navy-900">Dossiers</h3>
                @if ($hiddenFoldersCount > 0)
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $hiddenFoldersCount }} autre(s) dossier(s) ne vous sont pas affectés.
                    </p>
                @endif
            </header>

            @forelse ($folders as $folder)
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 px-6 sm:px-8 py-4 border-b border-slate-100 last:border-b-0">
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('cabinet.folders.show', $folder) }}" class="block font-medium text-navy-900 truncate hover:text-gold-700 transition">{{ $folder->title }}</a>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $folder->reference }} · {{ $folder->mission_type }}@if ($folder->period) · {{ $folder->period }}@endif
                            @if ($folder->manager) · {{ $folder->manager->full_name }}@endif
                        </p>
                    </div>
                    @if ($folder->due_date)
                        <p class="text-xs text-slate-400 whitespace-nowrap">Échéance {{ $folder->due_date->format('d/m/Y') }}</p>
                    @endif
                    <span class="inline-flex self-start sm:self-auto items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap"
                        style="color: {{ $folder->status->color }}; background-color: {{ $folder->status->color }}1a">
                        {{ $folder->status->name }}
                    </span>
                </div>
            @empty
                <p class="px-6 sm:px-8 py-10 text-center text-sm text-slate-500">Aucun dossier à afficher.</p>
            @endforelse
        </section>
    </div>

    {{-- Confirmations --}}
    @canany(['lock', 'unlock'], $organization)
        <x-confirm-modal
            id="activation-organization-modal"
            :title="$organization->is_active ? 'Désactiver l\'organisation ?' : 'Réactiver l\'organisation ?'"
            :action="route('cabinet.organizations.activation', $organization)"
            method="PATCH"
            :confirm-label="$organization->is_active ? 'Désactiver' : 'Réactiver'"
            :variant="$organization->is_active ? 'danger' : 'success'"
        >
            @if ($organization->is_active)
                Les utilisateurs de <strong class="font-semibold text-navy-900">{{ $organization->name }}</strong> ne pourront plus se connecter à l'espace client.
                Vous pourrez la réactiver à tout moment.
            @else
                Les utilisateurs actifs de <strong class="font-semibold text-navy-900">{{ $organization->name }}</strong> pourront de nouveau se connecter à l'espace client.
            @endif
        </x-confirm-modal>
    @endcanany

    @can('delete', $organization)
        <x-confirm-modal
            id="delete-organization-modal"
            title="Supprimer l'organisation ?"
            :action="route('cabinet.organizations.destroy', $organization)"
            method="DELETE"
            confirm-label="Supprimer"
        >
            <strong class="font-semibold text-navy-900">{{ $organization->name }}</strong> sera retirée de la liste des organisations.
            Une organisation qui a des dossiers ne peut pas être supprimée : désactivez-la plutôt.
        </x-confirm-modal>
    @endcan
</x-layouts.cabinet>

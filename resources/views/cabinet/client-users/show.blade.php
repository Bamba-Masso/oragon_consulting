<x-layouts.cabinet :title="$clientUser->full_name">
    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Retour et actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <a href="{{ route('cabinet.client-users.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-navy-900 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                Utilisateurs clients
            </a>

            <div class="flex flex-wrap gap-2">
                @can('update', $clientUser)
                    <button type="button" data-modal-target="edit-client-user-{{ $clientUser->id }}" data-modal-toggle="edit-client-user-{{ $clientUser->id }}"
                        class="inline-flex items-center px-4 py-2 rounded-xl bg-navy-900 hover:bg-navy-800 text-sm font-medium text-white transition">
                        Modifier
                    </button>
                @endcan

                @canany(['lock', 'unlock'], $clientUser)
                    <button type="button" data-modal-target="activation-client-user-{{ $clientUser->id }}" data-modal-toggle="activation-client-user-{{ $clientUser->id }}"
                        class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-navy-900 hover:bg-slate-50 transition">
                        {{ $clientUser->is_active ? 'Désactiver l\'accès' : 'Réactiver l\'accès' }}
                    </button>
                @endcanany
            </div>
        </div>

        <x-flash />

        <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            {{-- En-tête --}}
            <div class="bg-linear-to-r from-navy-900 to-navy-800 px-6 py-6 sm:px-8 text-white">
                <div class="flex items-center gap-4">
                    @if ($clientUser->avatar_url)
                        <img src="{{ $clientUser->avatar_url }}" alt="Photo de {{ $clientUser->full_name }}"
                            class="w-16 h-16 shrink-0 rounded-full object-cover ring-2 ring-white/80">
                    @else
                        <span class="w-16 h-16 shrink-0 flex items-center justify-center rounded-full bg-white text-lg font-bold text-navy-900">
                            {{ $clientUser->initials }}
                        </span>
                    @endif
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold leading-tight break-words">{{ $clientUser->full_name }}</h2>
                        <p class="mt-0.5 text-sm text-white/70">{{ $clientUser->job_title ?: 'Utilisateur du portail client' }}</p>
                        <div class="mt-2">
                            <x-organization-status :active="$clientUser->is_active" active-label="Actif" inactive-label="Désactivé" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Informations --}}
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 px-6 py-6 sm:px-8 text-sm">
                <div class="min-w-0">
                    <dt class="text-xs text-slate-400">Entreprise</dt>
                    <dd class="mt-1 font-medium text-navy-900">
                        @if ($clientUser->organization)
                            @can('view', $clientUser->organization)
                                <a href="{{ route('cabinet.organizations.show', $clientUser->organization) }}" class="hover:text-gold-700 transition">{{ $clientUser->organization->name }}</a>
                            @else
                                {{ $clientUser->organization->name }}
                            @endcan
                        @else
                            —
                        @endif
                    </dd>
                </div>

                @foreach ([
                    'Nom d\'utilisateur' => $clientUser->username,
                    'E-mail' => $clientUser->email,
                    'Téléphone' => $clientUser->phone,
                    'Fonction' => $clientUser->job_title,
                    'Adresse' => $clientUser->address,
                    'Dernière connexion' => $clientUser->last_login_at?->format('d/m/Y à H:i') ?? 'Jamais',
                    'Compte créé le' => $clientUser->created_at->format('d/m/Y').($clientUser->creator ? ' par '.$clientUser->creator->full_name : ''),
                ] as $label => $value)
                    <div class="min-w-0">
                        <dt class="text-xs text-slate-400">{{ $label }}</dt>
                        <dd class="mt-1 font-medium text-navy-900 break-words">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>

    @can('update', $clientUser)
        @include('cabinet.client-users.edit', ['clientUser' => $clientUser])
    @endcan

    @canany(['lock', 'unlock'], $clientUser)
        @include('cabinet.client-users.activation', ['clientUser' => $clientUser])
    @endcanany
</x-layouts.cabinet>

<x-layouts.cabinet :title="$collaborator->full_name">
    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Retour et actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <a href="{{ route('cabinet.users.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-navy-900 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                Collaborateurs
            </a>

            <div class="flex flex-wrap gap-2">
                @can('update', $collaborator)
                    <button type="button" data-modal-target="edit-user-{{ $collaborator->id }}" data-modal-toggle="edit-user-{{ $collaborator->id }}"
                        class="inline-flex items-center px-4 py-2 rounded-xl bg-navy-900 hover:bg-navy-800 text-sm font-medium text-white transition">
                        Modifier
                    </button>
                @endcan

                @canany(['lock', 'unlock'], $collaborator)
                    <button type="button" data-modal-target="activation-user-{{ $collaborator->id }}" data-modal-toggle="activation-user-{{ $collaborator->id }}"
                        class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-navy-900 hover:bg-slate-50 transition">
                        {{ $collaborator->is_active ? 'Désactiver le compte' : 'Réactiver le compte' }}
                    </button>
                @endcanany
            </div>
        </div>

        <x-flash />

        <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            {{-- En-tête --}}
            <div class="bg-linear-to-r from-navy-900 to-navy-800 px-6 py-6 sm:px-8 text-white">
                <div class="flex items-center gap-4">
                    @if ($collaborator->avatar_url)
                        <img src="{{ $collaborator->avatar_url }}" alt="Photo de {{ $collaborator->full_name }}"
                            class="w-16 h-16 shrink-0 rounded-full object-cover ring-2 ring-white/80">
                    @else
                        <span class="w-16 h-16 shrink-0 flex items-center justify-center rounded-full bg-white text-lg font-bold text-navy-900">
                            {{ $collaborator->initials }}
                        </span>
                    @endif
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold leading-tight break-words">{{ $collaborator->full_name }}</h2>
                        <p class="mt-0.5 text-sm text-white/70">{{ $collaborator->role_label ?? 'Collaborateur du cabinet' }}</p>
                        <div class="mt-2">
                            <x-organization-status :active="$collaborator->is_active" active-label="Actif" inactive-label="Désactivé" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Informations --}}
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 px-6 py-6 sm:px-8 text-sm">
                @foreach ([
                    'Rôle' => $collaborator->role_label,
                    'Nom d\'utilisateur' => $collaborator->username,
                    'E-mail' => $collaborator->email,
                    'Téléphone' => $collaborator->phone,
                    'Adresse' => $collaborator->address,
                    'Dernière connexion' => $collaborator->last_login_at?->format('d/m/Y à H:i') ?? 'Jamais',
                    'Compte créé le' => $collaborator->created_at->format('d/m/Y'),
                ] as $label => $value)
                    <div class="min-w-0">
                        <dt class="text-xs text-slate-400">{{ $label }}</dt>
                        <dd class="mt-1 font-medium text-navy-900 break-words">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>

    @can('update', $collaborator)
        @include('cabinet.users.edit', ['collaborator' => $collaborator])
    @endcan

    @canany(['lock', 'unlock'], $collaborator)
        @include('cabinet.users.activation', ['collaborator' => $collaborator])
    @endcanany
</x-layouts.cabinet>

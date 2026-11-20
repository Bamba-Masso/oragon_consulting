{{--
    Page « Mon profil » commune aux deux espaces (cabinet et client).
    Chaque espace fournit ses routes, son badge (rôle ou organisation) et ses informations complémentaires.
--}}
@props([
    'user',
    'updateRoute',
    'passwordRoute',
    'badge' => null,
    'details' => [],
    'note' => null,
    'withJobTitle' => false,
])
@php
    $initials = collect(explode(' ', $user->full_name))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->join('');

    $inputClass = fn (string $bag, string $field): string => 'block w-full rounded-xl border bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-4 transition '
        .($errors->getBag($bag)->has($field)
            ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10'
            : 'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15');
@endphp

<div class="max-w-3xl mx-auto space-y-6">

    <x-flash />

    {{-- Identité --}}
    <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
            <span class="w-16 h-16 shrink-0 flex items-center justify-center rounded-full bg-navy-900 text-lg font-semibold text-gold-500">{{ $initials }}</span>
            <div class="min-w-0">
                <h2 class="text-xl font-bold tracking-tight text-navy-900 truncate">{{ $user->full_name }}</h2>
                <p class="mt-0.5 text-sm text-slate-500 truncate">{{ $user->email }}</p>
                @if ($badge)
                    <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-gold-500/10 px-2.5 py-0.5 text-xs font-medium text-gold-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-gold-500"></span>
                        {{ $badge }}
                    </span>
                @endif
            </div>
        </div>

        <dl class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            @foreach ([
                "Nom d'utilisateur" => $user->username,
                ...$details,
                'Membre depuis' => $user->created_at->format('d/m/Y'),
                'Dernière connexion' => $user->last_login_at?->format('d/m/Y à H:i') ?? '—',
            ] as $label => $value)
                <div>
                    <dt class="text-xs text-slate-400">{{ $label }}</dt>
                    <dd class="mt-1 font-medium text-navy-900 truncate">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Informations personnelles --}}
    <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 p-6 sm:p-8">
        <header class="mb-6">
            <h3 class="text-base font-semibold text-navy-900">Informations personnelles</h3>
            @if ($note)
                <p class="mt-1 text-sm text-slate-500">{{ $note }}</p>
            @endif
        </header>

        <form method="POST" action="{{ route($updateRoute) }}" class="space-y-5" novalidate>
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="first_name" class="block mb-2 text-sm font-medium text-navy-900">Prénom</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required autocomplete="given-name"
                        class="{{ $inputClass('profile', 'first_name') }}">
                    @error('first_name', 'profile')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="last_name" class="block mb-2 text-sm font-medium text-navy-900">Nom</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required autocomplete="family-name"
                        class="{{ $inputClass('profile', 'last_name') }}">
                    @error('last_name', 'profile')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-navy-900">E-mail</label>
                    <input type="email" id="email" value="{{ $user->email }}" disabled
                        class="block w-full rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm text-slate-500 cursor-not-allowed">
                </div>

                <div>
                    <label for="phone" class="block mb-2 text-sm font-medium text-navy-900">Téléphone</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" autocomplete="tel"
                        class="{{ $inputClass('profile', 'phone') }}" placeholder="+225 07 00 00 00 00">
                    @error('phone', 'profile')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if ($withJobTitle)
                    <div class="sm:col-span-2">
                        <label for="job_title" class="block mb-2 text-sm font-medium text-navy-900">Fonction</label>
                        <input type="text" id="job_title" name="job_title" value="{{ old('job_title', $user->job_title) }}" autocomplete="organization-title"
                            class="{{ $inputClass('profile', 'job_title') }}" placeholder="Ex. : Responsable administratif">
                        @error('job_title', 'profile')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold transition focus:outline-none focus:ring-4 focus:ring-gold-500/25">
                    Enregistrer
                </button>
            </div>
        </form>
    </section>

    {{-- Mot de passe --}}
    <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 p-6 sm:p-8">
        <header class="mb-6">
            <h3 class="text-base font-semibold text-navy-900">Mot de passe</h3>
            <p class="mt-1 text-sm text-slate-500">Choisissez un mot de passe long, que vous n'utilisez nulle part ailleurs.</p>
        </header>

        <form method="POST" action="{{ route($passwordRoute) }}" class="space-y-5" novalidate>
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block mb-2 text-sm font-medium text-navy-900">Mot de passe actuel</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                    class="{{ $inputClass('password', 'current_password') }}">
                @error('current_password', 'password')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="password" class="block mb-2 text-sm font-medium text-navy-900">Nouveau mot de passe</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password"
                        class="{{ $inputClass('password', 'password') }}">
                    @error('password', 'password')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block mb-2 text-sm font-medium text-navy-900">Confirmation</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                        class="{{ $inputClass('password', 'password') }}">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold transition focus:outline-none focus:ring-4 focus:ring-gold-500/25">
                    Mettre à jour le mot de passe
                </button>
            </div>
        </form>
    </section>

</div>

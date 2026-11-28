{{--
    Gabarit des pages d'accès (connexion, mot de passe oublié, nouveau mot de passe) des deux espaces.
--}}
@props([
    'space',
    'title',
    'heading',
    'description',
])
@php
    $isCabinet = $space === 'cabinet';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }} — {{ config('app.name', 'Cabinet Conseil & Expertise') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-amber-50/40 text-slate-800 antialiased selection:bg-gold-600 selection:text-white">

    <main class="min-h-screen flex flex-col items-center justify-center px-4 py-12">

        <div class="relative w-full max-w-md overflow-hidden bg-white rounded-2xl shadow-2xl shadow-navy-900/[0.06] ring-1 ring-slate-200/70 p-8 sm:p-10">
            <!-- Filet doré -->
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-gold-500 via-gold-600 to-gold-700"></div>

            <!-- En-tête -->
            <div class="mb-9 space-y-3">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-3">
                        <span class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.2em] {{ $isCabinet ? 'text-navy-900' : 'text-gold-700' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-gold-500"></span>
                            {{ $isCabinet ? 'Espace collaborateur' : 'Espace client' }}
                        </span>
                        <h1 class="text-3xl font-bold tracking-tight text-navy-900">{{ $heading }}</h1>
                    </div>

                    <!-- Logo -->
                    <a href="{{ url('/') }}" class="shrink-0 focus:outline-none">
                        <img src="{{ asset('logo.png') }}" alt="Logo du Cabinet" class="h-12 w-auto object-contain">
                    </a>
                </div>
                <p class="text-sm leading-relaxed text-slate-500">{{ $description }}</p>
            </div>

            {{ $slot }}
        </div>

        <p class="mt-8 flex items-center gap-2 text-xs text-slate-400">
            <svg class="w-3.5 h-3.5 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Connexion sécurisée · © {{ date('Y') }} {{ config('app.name', 'Cabinet Conseil & Expertise') }}
        </p>

    </main>

    {{ $scripts ?? '' }}
</body>
</html>

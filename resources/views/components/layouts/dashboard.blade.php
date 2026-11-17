{{--
    Gabarit commun aux deux espaces connectés (cabinet et client).
    Ne pas l'utiliser directement dans une page : passer par <x-layouts.cabinet> ou <x-layouts.client>.
--}}
@props([
    'title',
    'space',
    'user',
    'subtitle' => null,
    'logoutRoute',
])
@php
    $initials = collect(explode(' ', $user->full_name))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->join('');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — {{ config('app.name', 'Cabinet Conseil & Expertise') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased selection:bg-gold-600 selection:text-white">

    <!-- Barre latérale -->
    <aside id="sidebar" aria-label="Navigation"
        class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col bg-white border-r border-slate-200/80 transition-transform -translate-x-full lg:translate-x-0">

        <div class="h-16 flex items-center px-6 border-b border-slate-100">
            <a href="{{ url('/') }}" class="focus:outline-none">
                <img src="{{ asset('logo.png') }}" alt="Logo du Cabinet" class="h-9 w-auto object-contain">
            </a>
        </div>

        <div class="px-6 pt-6 pb-2">
            <span class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-gold-700">
                <span class="w-1.5 h-1.5 rounded-full bg-gold-500"></span>
                {{ $space }}
            </span>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-2 space-y-1">
            {{ $navigation }}
        </nav>
    </aside>

    <div class="lg:pl-64">

        <!-- Barre du haut -->
        <header class="sticky top-0 z-30 h-16 flex items-center gap-3 px-4 sm:px-8 bg-white/80 backdrop-blur border-b border-slate-200/80">
            <button type="button" data-drawer-target="sidebar" data-drawer-toggle="sidebar" aria-controls="sidebar"
                class="lg:hidden -ml-1 p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-navy-900 transition focus:outline-none">
                <span class="sr-only">Ouvrir le menu</span>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>

            <h1 class="text-lg font-bold tracking-tight text-navy-900 truncate">{{ $title }}</h1>

            <!-- Menu utilisateur -->
            <div class="ml-auto">
                <button type="button" id="userMenuButton" data-dropdown-toggle="userDropdown" data-dropdown-placement="bottom-end"
                    class="flex items-center gap-3 rounded-xl px-2 py-1.5 hover:bg-slate-100 transition focus:outline-none focus:ring-4 focus:ring-gold-500/15">
                    <span class="w-9 h-9 flex items-center justify-center rounded-full bg-navy-900 text-xs font-semibold text-gold-500">{{ $initials }}</span>
                    <span class="hidden sm:block text-left leading-tight">
                        <span class="block text-sm font-semibold text-navy-900">{{ $user->full_name }}</span>
                        @if ($subtitle)
                            <span class="block text-xs text-slate-500">{{ $subtitle }}</span>
                        @endif
                    </span>
                    <svg class="hidden sm:block w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>

                <div id="userDropdown" class="z-50 hidden w-64 bg-white rounded-xl shadow-lg shadow-navy-900/5 ring-1 ring-slate-200/70 divide-y divide-slate-100">
                    <div class="px-4 py-3">
                        <p class="text-sm font-semibold text-navy-900 truncate">{{ $user->full_name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $user->email }}</p>
                    </div>
                    <form method="POST" action="{{ route($logoutRoute) }}" class="p-1.5">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-red-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                            Se déconnecter
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Contenu de la page -->
        <main class="px-4 sm:px-8 py-8">
            {{ $slot }}
        </main>
    </div>

</body>
</html>

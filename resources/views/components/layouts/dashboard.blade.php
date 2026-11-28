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
    'profileRoute' => null,
    'notificationsReadRoute' => null,
    'notificationsOpenRoute' => null,
])
@php
    $initials = collect(explode(' ', $user->full_name))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->join('');

    // Cloche : les 8 dernières notifications (format attendu dans data : message, url facultative).
    $unreadNotificationsCount = $user->unreadNotifications()->count();
    $latestNotifications = $user->notifications()->latest()->take(8)->get();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — {{ config('app.name', 'Cabinet Conseil & Expertise') }}</title>
    <script src="https://kit.fontawesome.com/55f637bece.js" crossorigin="anonymous"></script>

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

            <!-- Notifications -->
            <div class="ml-auto">
                <button type="button" id="notificationButton" data-dropdown-toggle="notificationDropdown" data-dropdown-placement="bottom-end"
                    class="relative p-2 rounded-xl text-slate-500 hover:bg-slate-100 hover:text-navy-900 transition focus:outline-none focus:ring-4 focus:ring-gold-500/15">
                    <span class="sr-only">Notifications{{ $unreadNotificationsCount > 0 ? " ({$unreadNotificationsCount} non lues)" : '' }}</span>
                    {{-- Le compteur est accroché au coin de l'icône (et non du bouton) pour rester bien placé --}}
                    <span class="relative block">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                        @if ($unreadNotificationsCount > 0)
                            <span class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-red-600 ring-2 ring-white text-[10px] font-bold leading-none tabular-nums text-white" aria-hidden="true">{{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}</span>
                        @endif
                    </span>
                </button>

                <div id="notificationDropdown" class="z-50 hidden w-80 max-w-[calc(100vw-2rem)] bg-white rounded-xl shadow-lg shadow-navy-900/5 ring-1 ring-slate-200/70 overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100">
                        <p class="text-sm font-semibold text-navy-900">Notifications</p>
                        @if ($unreadNotificationsCount > 0 && $notificationsReadRoute)
                            <form method="POST" action="{{ route($notificationsReadRoute) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-xs font-medium text-gold-700 hover:text-gold-600 transition">Tout marquer comme lu</button>
                            </form>
                        @endif
                    </div>

                    <div class="max-h-96 overflow-y-auto divide-y divide-slate-100">
                        @forelse ($latestNotifications as $notification)
                            @php
                                // Le clic passe par la route « open » : la notification est marquée lue avant d'ouvrir la page.
                                $notificationUrl = ($notification->data['url'] ?? null) && $notificationsOpenRoute
                                    ? route($notificationsOpenRoute, $notification->id)
                                    : ($notification->data['url'] ?? null);
                            @endphp
                            <a @if ($notificationUrl) href="{{ $notificationUrl }}" @endif
                                @class(['flex gap-3 px-4 py-3 transition', 'hover:bg-slate-50' => $notificationUrl, 'bg-navy-900/[0.03]' => $notification->unread()])>
                                <span @class(['mt-1.5 w-2 h-2 shrink-0 rounded-full', 'bg-navy-900' => $notification->unread(), 'bg-transparent' => $notification->read()])></span>
                                <span class="min-w-0">
                                    <span @class(['block text-sm break-words', 'font-medium text-navy-900' => $notification->unread(), 'text-slate-600' => $notification->read()])>
                                        {{ $notification->data['message'] ?? 'Nouvelle notification' }}
                                    </span>
                                    <span class="block mt-0.5 text-xs text-slate-400">{{ $notification->created_at->locale('fr')->diffForHumans() }}</span>
                                </span>
                            </a>
                        @empty
                            <p class="px-4 py-8 text-center text-sm text-slate-500">Aucune notification.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Menu utilisateur -->
            <div>
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
                    @if ($profileRoute)
                        <div class="p-1.5">
                            <a href="{{ route($profileRoute) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-navy-900 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                Mon profil
                            </a>
                        </div>
                    @endif
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

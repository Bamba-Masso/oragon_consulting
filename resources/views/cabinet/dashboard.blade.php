<x-layouts.cabinet title="Tableau de bord">
    @php
        $tileClass = 'group flex items-center gap-4 bg-white rounded-2xl ring-1 ring-slate-200/70 p-5 hover:ring-gold-500/60 hover:shadow-md hover:shadow-navy-900/5 transition focus:outline-none focus:ring-2 focus:ring-gold-500/50';
        $iconClass = 'w-11 h-11 shrink-0 flex items-center justify-center rounded-xl';
    @endphp

    <div class="space-y-6">

        {{-- Accueil --}}
        <section class="rounded-2xl bg-linear-to-r from-navy-900 to-navy-800 px-6 py-6 sm:px-8 text-white">
            <p class="text-xs text-white/60">{{ ucfirst(now()->locale('fr')->translatedFormat('l j F Y')) }}</p>
            <h2 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight">
                Bonjour {{ $user->first_name ?: $user->username }}
            </h2>
            <p class="mt-1 text-sm text-white/70">Voici où en sont vos dossiers aujourd'hui.</p>
        </section>

        {{-- Chiffres clés --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @if ($activeFoldersCount !== null)
                <a href="{{ route('cabinet.folders.index') }}" class="{{ $tileClass }}">
                    <span class="{{ $iconClass }} bg-navy-900/5 text-navy-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-2xl font-bold text-navy-900">{{ $activeFoldersCount }}</span>
                        <span class="block text-sm text-slate-500">{{ $activeFoldersCount > 1 ? 'Dossiers en cours' : 'Dossier en cours' }}</span>
                        @if ($overdueFoldersCount > 0)
                            <span class="block text-xs font-medium text-red-600">dont {{ $overdueFoldersCount }} en retard</span>
                        @endif
                    </span>
                </a>
            @endif

            @if ($openTasksCount !== null)
                <a href="{{ route('folder.tasks') }}" class="{{ $tileClass }}">
                    <span class="{{ $iconClass }} bg-navy-900/5 text-navy-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-2xl font-bold text-navy-900">{{ $openTasksCount }}</span>
                        <span class="block text-sm text-slate-500">{{ $openTasksCount > 1 ? 'Tâches à faire' : 'Tâche à faire' }}</span>
                        @if ($overdueTasksCount > 0)
                            <span class="block text-xs font-medium text-red-600">dont {{ $overdueTasksCount }} en retard</span>
                        @endif
                    </span>
                </a>
            @endif

            @if ($pendingRequestsCount !== null)
                <a href="{{ route('cabinet.document-requests.index') }}" class="{{ $tileClass }}">
                    <span class="{{ $iconClass }} bg-gold-500/15 text-gold-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-2xl font-bold text-navy-900">{{ $pendingRequestsCount }}</span>
                        <span class="block text-sm text-slate-500">{{ $pendingRequestsCount > 1 ? 'Demandes en attente du client' : 'Demande en attente du client' }}</span>
                        @if ($receivedRequestsCount > 0)
                            <span class="block text-xs font-medium text-gold-700">{{ $receivedRequestsCount }} en discussion</span>
                        @endif
                    </span>
                </a>
            @endif

            @if ($documentsToSendCount !== null)
                <a href="{{ route('cabinet.documents.index') }}" class="{{ $tileClass }}">
                    <span class="{{ $iconClass }} bg-emerald-50 text-emerald-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-2xl font-bold text-navy-900">{{ $documentsToSendCount }}</span>
                        <span class="block text-sm text-slate-500">{{ $documentsToSendCount > 1 ? 'Documents à envoyer' : 'Document à envoyer' }}</span>
                    </span>
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Prochaines tâches --}}
            @if ($nextTasks !== null)
                <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-navy-900">Mes prochaines tâches</h3>
                        <a href="{{ route('folder.tasks') }}" class="shrink-0 text-xs font-medium text-gold-700 hover:text-gold-600 transition">Tout voir</a>
                    </div>

                    @forelse ($nextTasks as $task)
                        <div class="flex items-center gap-3 px-6 py-3 border-b border-slate-100 last:border-b-0">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('folder.tasks.show', $task) }}" class="block text-sm font-medium text-navy-900 truncate hover:text-gold-700 transition">{{ $task->title }}</a>
                                <p class="text-xs text-slate-500 truncate">
                                    {{ $task->folder->reference }}
                                    @if ($task->due_date)
                                        · <span @class(['font-semibold text-red-600' => $task->due_date->isBefore(today())])>échéance {{ $task->due_date->format('d/m/Y') }}</span>
                                    @endif
                                </p>
                            </div>
                            <x-status-badge :label="$task->status->name" :color="$task->status->color" />
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-sm text-slate-500">Aucune tâche à faire pour le moment.</p>
                    @endforelse
                </section>
            @endif

            {{-- Échéances proches --}}
            @if ($upcomingFolders !== null)
                <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-navy-900">Dossiers à rendre dans les {{ $upcomingDays }} jours</h3>
                        <a href="{{ route('cabinet.folders.index') }}" class="shrink-0 text-xs font-medium text-gold-700 hover:text-gold-600 transition">Tout voir</a>
                    </div>

                    @forelse ($upcomingFolders as $folder)
                        <div class="flex items-center gap-3 px-6 py-3 border-b border-slate-100 last:border-b-0">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('cabinet.folders.show', $folder) }}" class="block text-sm font-medium text-navy-900 truncate hover:text-gold-700 transition">{{ $folder->title }}</a>
                                <p class="text-xs text-slate-500 truncate">
                                    {{ $folder->reference }}@if ($folder->organization) · {{ $folder->organization->name }}@endif
                                    · <span @class(['font-semibold text-red-600' => $folder->is_overdue])>{{ $folder->is_overdue ? 'en retard depuis le' : 'à rendre le' }} {{ $folder->due_date->format('d/m/Y') }}</span>
                                </p>
                            </div>
                            <x-status-badge :label="$folder->status->name" :color="$folder->status->color" />
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-sm text-slate-500">Aucun dossier à rendre prochainement.</p>
                    @endforelse
                </section>
            @endif
        </div>
    </div>
</x-layouts.cabinet>

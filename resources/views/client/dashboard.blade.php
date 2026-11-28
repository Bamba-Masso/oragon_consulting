<x-layouts.client title="Tableau de bord">
    @php
        $tileClass = 'group flex items-center gap-4 bg-white rounded-2xl ring-1 ring-slate-200/70 p-5 hover:ring-gold-500/60 hover:shadow-md hover:shadow-navy-900/5 transition focus:outline-none focus:ring-2 focus:ring-gold-500/50';
        $iconClass = 'w-11 h-11 shrink-0 flex items-center justify-center rounded-xl';
    @endphp

    <div class="space-y-6">

        {{-- Accueil --}}
        <section class="rounded-2xl bg-linear-to-r from-navy-900 to-navy-800 px-6 py-6 sm:px-8 text-white">
            <p class="text-xs text-white/60">{{ ucfirst(now()->locale('fr')->translatedFormat('l j F Y')) }}</p>
            <h2 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight">
                Bonjour {{ $clientUser->first_name ?: $clientUser->username }}
            </h2>
            <p class="mt-1 text-sm text-white/70">
                @if ($clientUser->organization)
                    Retrouvez ici le suivi des dossiers de {{ $clientUser->organization->name }}.
                @else
                    Retrouvez ici le suivi de vos dossiers.
                @endif
            </p>
        </section>

        {{-- Chiffres clés --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @if ($activeFoldersCount !== null)
                <a href="{{ route('client.folders.index') }}" class="{{ $tileClass }}">
                    <span class="{{ $iconClass }} bg-navy-900/5 text-navy-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-2xl font-bold text-navy-900">{{ $activeFoldersCount }}</span>
                        <span class="block text-sm text-slate-500">{{ $activeFoldersCount > 1 ? 'Dossiers en cours' : 'Dossier en cours' }}</span>
                    </span>
                </a>
            @endif

            @if ($openRequestsCount !== null)
                <a href="{{ route('client.document-requests.index') }}" class="{{ $tileClass }}">
                    <span @class([$iconClass, 'bg-gold-500/15 text-gold-700' => $openRequestsCount > 0, 'bg-navy-900/5 text-navy-900' => $openRequestsCount === 0])>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span @class(['block text-2xl font-bold', 'text-gold-700' => $openRequestsCount > 0, 'text-navy-900' => $openRequestsCount === 0])>{{ $openRequestsCount }}</span>
                        <span class="block text-sm text-slate-500">{{ $openRequestsCount > 1 ? 'Demandes de pièces en cours' : 'Demande de pièces en cours' }}</span>
                    </span>
                </a>
            @endif

            @if ($documentsCount !== null)
                <a href="{{ route('client.documents.index') }}" class="{{ $tileClass }}">
                    <span class="{{ $iconClass }} bg-emerald-50 text-emerald-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-2xl font-bold text-navy-900">{{ $documentsCount }}</span>
                        <span class="block text-sm text-slate-500">{{ $documentsCount > 1 ? 'Documents reçus' : 'Document reçu' }}</span>
                        @if ($recentDocumentsCount > 0)
                            <span class="block text-xs font-medium text-emerald-700">dont {{ $recentDocumentsCount }} ces {{ $recentDocumentsDays }} derniers jours</span>
                        @endif
                    </span>
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Pièces à fournir --}}
            @if ($openRequests !== null)
                <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-navy-900">Pièces demandées par le cabinet</h3>
                        <a href="{{ route('client.document-requests.index') }}" class="shrink-0 text-xs font-medium text-gold-700 hover:text-gold-600 transition">Tout voir</a>
                    </div>

                    @forelse ($openRequests as $documentRequest)
                        <div class="flex items-center gap-3 px-6 py-3 border-b border-slate-100 last:border-b-0">
                            <div class="min-w-0 flex-1">
                                @can('view', $documentRequest)
                                    <a href="{{ route('client.document-requests.show', $documentRequest) }}" class="block text-sm font-medium text-navy-900 truncate hover:text-gold-700 transition">{{ $documentRequest->title }}</a>
                                @else
                                    <p class="text-sm font-medium text-navy-900 truncate">{{ $documentRequest->title }}</p>
                                @endcan
                                <p class="text-xs text-slate-500 truncate">
                                    {{ $documentRequest->folder->reference }} · demandée le {{ $documentRequest->created_at->format('d/m/Y') }}
                                </p>
                            </div>
                            <x-status-badge :label="$documentRequest->status->name" :color="$documentRequest->status->color" />
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-sm text-slate-500">Aucune pièce à fournir pour le moment.</p>
                    @endforelse
                </section>
            @endif

            {{-- Derniers documents reçus --}}
            @if ($latestDocuments !== null)
                <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-navy-900">Derniers documents reçus</h3>
                        <a href="{{ route('client.documents.index') }}" class="shrink-0 text-xs font-medium text-gold-700 hover:text-gold-600 transition">Tout voir</a>
                    </div>

                    @forelse ($latestDocuments as $document)
                        <div class="flex items-center gap-3 px-6 py-3 border-b border-slate-100 last:border-b-0">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-navy-900 truncate">{{ $document->name }}</p>
                                <p class="text-xs text-slate-500 truncate">
                                    {{ $document->folder->reference }} · reçu le {{ $document->published_at?->format('d/m/Y') }}
                                </p>
                            </div>
                            @can('download', $document)
                                <a href="{{ route('client.documents.download', $document) }}" title="Télécharger" aria-label="Télécharger {{ $document->name }}"
                                    class="p-2 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-navy-900 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                </a>
                            @endcan
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-sm text-slate-500">Aucun document reçu pour le moment.</p>
                    @endforelse
                </section>
            @endif
        </div>
    </div>
</x-layouts.client>

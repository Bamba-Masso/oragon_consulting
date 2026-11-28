<x-layouts.client title="Mes dossiers">
    <div class="space-y-6">

        <x-flash />

        {{-- Recherche seule, sur toute la largeur --}}
        <form method="GET" action="{{ route('client.folders.index') }}" class="flex gap-3 w-full">
            <label for="search" class="sr-only">Rechercher</label>
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Référence, intitulé, mission, période…"
                    class="block w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition">
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium text-navy-900 hover:bg-slate-50 transition">
                Rechercher
            </button>
        </form>

        {{-- Liste en cartes --}}
        @if ($folders->isEmpty())
            <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 px-6 py-16 text-center">
                <svg class="mx-auto w-10 h-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/></svg>
                <p class="mt-3 text-sm font-medium text-navy-900">Aucun dossier</p>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $search !== '' ? 'Modifiez votre recherche.' : 'Les dossiers que le cabinet traite pour votre entreprise apparaîtront ici.' }}
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($folders as $folder)
                    <a href="{{ route('client.folders.show', $folder) }}"
                        class="group flex flex-col overflow-hidden bg-white rounded-2xl ring-1 ring-gold-500/50 shadow-md shadow-navy-900/5 hover:ring-gold-500 hover:shadow-lg hover:shadow-navy-900/10 transition focus:outline-none focus:ring-2 focus:ring-gold-500/50">
                        <span class="h-1.5 bg-navy-900" aria-hidden="true"></span>
                        <div class="p-5 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-mono text-xs text-slate-400">{{ $folder->reference }}</p>
                                <x-status-badge :label="$folder->status->name" :color="$folder->status->color" />
                            </div>

                            <p class="mt-2 font-semibold text-gold-700 truncate">{{ $folder->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 truncate">{{ $folder->mission_type }}@if ($folder->period) · {{ $folder->period }}@endif</p>

                            <p class="mt-4 inline-flex items-center gap-1.5 text-xs font-medium text-navy-900">
                                <svg class="w-4 h-4 text-gold-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                {{ $folder->sent_documents_count === 0 ? 'Aucun document' : $folder->sent_documents_count.' '.($folder->sent_documents_count > 1 ? 'documents' : 'document') }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between gap-4 px-5 py-3 border-t border-slate-100 text-xs text-slate-500">
                            <span class="truncate">{{ $folder->manager ? 'Suivi par '.$folder->manager->full_name : 'Interlocuteur à venir' }}</span>
                            <span class="shrink-0">Ouvert le {{ $folder->created_at->format('d/m/Y') }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

            {{ $folders->links() }}
        @endif
    </div>
</x-layouts.client>

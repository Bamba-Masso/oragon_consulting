<x-layouts.client title="Documents">
    <div class="space-y-6">

        <x-flash />

        {{-- Recherche seule, sur toute la largeur --}}
        <form method="GET" action="{{ route('client.documents.index') }}" class="flex gap-3 w-full">
            <label for="search" class="sr-only">Rechercher</label>
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Nom, catégorie, dossier…"
                    class="block w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition">
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium text-navy-900 hover:bg-slate-50 transition">
                Rechercher
            </button>
        </form>

        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            @if ($documents->isEmpty())
                <div class="px-6 py-16 text-center">
                    <p class="text-sm font-medium text-navy-900">Aucun document</p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $search !== '' ? 'Modifiez votre recherche.' : 'Les documents que le cabinet vous transmet apparaîtront ici.' }}
                    </p>
                </div>
            @else
                <table class="w-full table-fixed text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-2.5">Document</th>
                            <th scope="col" class="hidden sm:table-cell w-1/5 px-3 py-2.5">Catégorie</th>
                            <th scope="col" class="hidden md:table-cell w-36 px-3 py-2.5">Dossier</th>
                            <th scope="col" class="hidden md:table-cell w-32 px-3 py-2.5">Reçu le</th>
                            <th scope="col" class="w-24 px-5 py-2.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($documents as $document)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-navy-900 truncate">{{ $document->name }}</p>
                                    @if ($document->period)
                                        <p class="text-xs text-slate-500 truncate">{{ $document->period }}</p>
                                    @endif
                                </td>
                                <td class="hidden sm:table-cell px-3 py-3 text-slate-600 truncate">{{ $document->category }}</td>
                                <td class="hidden md:table-cell px-3 py-3 font-mono text-xs text-slate-600">{{ $document->folder->reference }}</td>
                                <td class="hidden md:table-cell px-3 py-3 text-slate-600">{{ $document->published_at?->format('d/m/Y') }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end">
                                        @can('download', $document)
                                            <a href="{{ route('client.documents.download', $document) }}" title="Télécharger" aria-label="Télécharger {{ $document->name }}"
                                                class="p-2 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-navy-900 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{ $documents->links() }}
    </div>
</x-layouts.client>

<x-layouts.cabinet title="Documents">
    <div class="space-y-6">

        <x-flash />

        {{-- Recherche et filtre par entreprise, sur toute la largeur --}}
        <form method="GET" action="{{ route('cabinet.documents.index') }}" class="flex flex-col md:flex-row gap-3 w-full">
            <label for="search" class="sr-only">Rechercher</label>
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Nom, catégorie, dossier, entreprise…"
                    class="block w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition">
            </div>

            <label for="organization" class="sr-only">Entreprise</label>
            <select id="organization" name="organization"
                class="md:w-56 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition">
                <option value="">Toutes les entreprises</option>
                @foreach ($organizations as $id => $name)
                    <option value="{{ $id }}" @selected($organizationId === $id)>{{ $name }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium text-navy-900 hover:bg-slate-50 transition">
                Filtrer
            </button>
        </form>

        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            @if ($documents->isEmpty())
                <div class="px-6 py-16 text-center">
                    <p class="text-sm font-medium text-navy-900">Aucun document</p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $search !== '' || $organizationId !== '' ? 'Modifiez votre recherche ou le filtre.' : 'Les documents s\'ajoutent depuis la fiche d\'un dossier.' }}
                    </p>
                </div>
            @else
                <table class="w-full table-fixed text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-2.5">Document</th>
                            <th scope="col" class="hidden lg:table-cell w-36 px-3 py-2.5">Dossier</th>
                            <th scope="col" class="hidden md:table-cell w-1/5 px-3 py-2.5">Entreprise</th>
                            <th scope="col" class="w-36 px-3 py-2.5">Statut</th>
                            <th scope="col" class="w-28 px-5 py-2.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($documents as $document)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-navy-900 truncate">{{ $document->name }}</p>
                                    <p class="text-xs text-slate-500 truncate">
                                        {{ $document->category }}@if ($document->period) · {{ $document->period }}@endif · {{ $document->created_at->format('d/m/Y') }}
                                    </p>
                                </td>
                                <td class="hidden lg:table-cell px-3 py-3">
                                    <a href="{{ route('cabinet.folders.show', $document->folder) }}" class="font-mono text-xs text-slate-600 hover:text-gold-700 transition">{{ $document->folder->reference }}</a>
                                </td>
                                <td class="hidden md:table-cell px-3 py-3 text-slate-600 truncate">{{ $document->organization?->name ?? '—' }}</td>
                                <td class="px-3 py-3">
                                    @if ($document->is_published_to_client)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 whitespace-nowrap">Envoyé le {{ $document->published_at->format('d/m/Y') }}</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 whitespace-nowrap">À envoyer</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        @can('publish', $document)
                                            <button type="button" data-modal-target="publish-document-{{ $document->id }}" data-modal-toggle="publish-document-{{ $document->id }}"
                                                title="Envoyer au client" aria-label="Envoyer {{ $document->name }} au client"
                                                class="p-2 rounded-lg text-slate-400 hover:bg-emerald-50 hover:text-emerald-600 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                                            </button>
                                        @endcan
                                        @can('download', $document)
                                            <a href="{{ route('cabinet.documents.download', $document) }}" title="Télécharger" aria-label="Télécharger {{ $document->name }}"
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

    @foreach ($documents as $document)
        @can('publish', $document)
            <x-confirm-modal :id="'publish-document-'.$document->id" title="Envoyer au client ?" variant="success"
                :action="route('cabinet.documents.publish', $document)" method="PATCH" confirm-label="Envoyer">
                <strong class="font-semibold text-navy-900">{{ $document->name }}</strong> apparaîtra dans l'espace de l'entreprise
                et ses utilisateurs seront prévenus par e-mail.
            </x-confirm-modal>
        @endcan
    @endforeach
</x-layouts.cabinet>

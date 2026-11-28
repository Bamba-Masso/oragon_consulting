<x-layouts.client :title="$folder->reference">
    <div class="max-w-7xl mx-auto space-y-6">

        <a href="{{ route('client.folders.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-navy-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            Mes dossiers
        </a>

        <x-flash />

        {{-- En-tête et informations --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            <div class="bg-linear-to-r from-navy-900 to-navy-800 px-6 py-6 sm:px-8 text-white">
                <p class="font-mono text-xs text-white/60">{{ $folder->reference }}</p>
                <h2 class="mt-1 text-lg font-bold leading-tight break-words">{{ $folder->title }}</h2>
                <p class="mt-1 text-sm text-white/70">{{ $folder->mission_type }}@if ($folder->period) · {{ $folder->period }}@endif</p>
                <div class="mt-3">
                    <x-status-badge :label="$folder->status->name" :color="$folder->status->color" on-dark />
                </div>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5 px-6 py-6 sm:px-8 text-sm">
                @foreach ([
                    'Suivi par' => $folder->manager?->full_name ?? 'Interlocuteur à venir',
                    'Ouvert le' => $folder->created_at->format('d/m/Y'),
                    'Terminé le' => $folder->closed_at?->format('d/m/Y'),
                ] as $label => $value)
                    @if ($value)
                        <div class="min-w-0">
                            <dt class="text-xs text-slate-400">{{ $label }}</dt>
                            <dd class="mt-1 font-medium text-navy-900 break-words">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        </section>

        {{-- Documents envoyés par le cabinet --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                <h3 class="font-semibold text-navy-900">Documents</h3>
                <span class="text-xs text-slate-500">{{ $documents->count() }} {{ $documents->count() > 1 ? 'documents' : 'document' }}</span>
            </div>

            @if ($documents->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-sm font-medium text-navy-900">Aucun document pour le moment</p>
                    <p class="mt-1 text-sm text-slate-500">Les documents que le cabinet vous envoie pour ce dossier apparaîtront ici.</p>
                </div>
            @else
                <table class="w-full table-fixed text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-2.5">Document</th>
                            <th scope="col" class="hidden sm:table-cell w-1/4 px-3 py-2.5">Catégorie</th>
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
        </section>
    </div>
</x-layouts.client>

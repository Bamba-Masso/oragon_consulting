{{--
    En-tête et informations d'une demande de pièces, communs au cabinet et au client.
    Attend une demande avec folder (et organization côté cabinet), status, requester, responder et closer chargés.
    Le slot « actions » reçoit les boutons propres à chaque espace.
--}}
@props(['documentRequest', 'backUrl', 'backLabel'])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-navy-900 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        {{ $backLabel }}
    </a>

    @isset($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>

<x-flash />

<section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
    <div class="bg-linear-to-r from-navy-900 to-navy-800 px-6 py-6 sm:px-8 text-white">
        <p class="font-mono text-xs text-white/60">{{ $documentRequest->reference }}</p>
        <h2 class="mt-1 text-lg font-bold leading-tight break-words">{{ $documentRequest->title }}</h2>
        <p class="mt-1 text-sm text-white/70">
            {{ $documentRequest->folder->reference }} · {{ $documentRequest->folder->title }}@if ($documentRequest->folder->relationLoaded('organization') && $documentRequest->folder->organization) · {{ $documentRequest->folder->organization->name }}@endif
        </p>
        <div class="mt-3">
            <x-status-badge :label="$documentRequest->status->name" :color="$documentRequest->status->color" on-dark />
        </div>
    </div>

    <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5 px-6 py-6 sm:px-8 text-sm">
        @foreach ([
            'Demandée le' => $documentRequest->created_at->format('d/m/Y').($documentRequest->requester ? ' par '.$documentRequest->requester->full_name : ''),
            'Dernière relance' => $documentRequest->last_reminded_at?->format('d/m/Y à H:i'),
            'Réponse de l\'entreprise' => $documentRequest->responded_at
                ? $documentRequest->responded_at->format('d/m/Y').($documentRequest->responder ? ' par '.$documentRequest->responder->full_name : '')
                : null,
            'Clôturée le' => $documentRequest->closed_at
                ? $documentRequest->closed_at->format('d/m/Y').($documentRequest->closer ? ' par '.$documentRequest->closer->full_name : '')
                : null,
        ] as $label => $value)
            <div class="min-w-0">
                <dt class="text-xs text-slate-400">{{ $label }}</dt>
                <dd class="mt-1 font-medium text-navy-900 break-words">{{ $value ?: '—' }}</dd>
            </div>
        @endforeach

        <div class="sm:col-span-2 lg:col-span-3 min-w-0">
            <dt class="text-xs text-slate-400">Pièces attendues</dt>
            <dd class="mt-1 text-slate-700 whitespace-pre-line break-words">{{ $documentRequest->description ?: '—' }}</dd>
        </div>

        @if ($documentRequest->rejection_reason)
            <div class="sm:col-span-2 lg:col-span-3 min-w-0 rounded-xl bg-red-50 px-4 py-3">
                <dt class="text-xs font-medium text-red-700">Motif du rejet</dt>
                <dd class="mt-1 text-red-800 whitespace-pre-line break-words">{{ $documentRequest->rejection_reason }}</dd>
            </div>
        @endif
    </dl>
</section>

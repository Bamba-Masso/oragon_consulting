{{--
    Progression d'un dossier : tâches terminées sur les tâches à faire (les tâches annulées ne comptent pas).
    on-dark : version pour le bandeau bleu marine de la fiche dossier.
--}}
@props(['completed' => 0, 'total' => 0, 'onDark' => false])
@php
    $percentage = $total > 0 ? (int) round($completed / $total * 100) : 0;
    $isComplete = $total > 0 && $completed === $total;
@endphp
<div {{ $attributes }}>
    <div @class(['flex items-center justify-between gap-3 text-xs', 'text-white/70' => $onDark, 'text-slate-500' => ! $onDark])>
        <span>Progression</span>
        <span @class(['font-medium', 'text-white' => $onDark, 'text-navy-900' => ! $onDark])>
            @if ($total === 0)
                Aucune tâche
            @else
                {{ $completed }} / {{ $total }} {{ $total > 1 ? 'tâches terminées' : 'tâche terminée' }} · {{ $percentage }} %
            @endif
        </span>
    </div>
    <div @class(['mt-1.5 h-1.5 rounded-full overflow-hidden', 'bg-white/15' => $onDark, 'bg-slate-100' => ! $onDark])
        role="progressbar" aria-label="Progression du dossier" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percentage }}">
        <div @class(['h-full rounded-full transition-all', 'bg-emerald-500' => $isComplete, 'bg-gold-500' => ! $isComplete])
            style="width: {{ $percentage }}%"></div>
    </div>
</div>

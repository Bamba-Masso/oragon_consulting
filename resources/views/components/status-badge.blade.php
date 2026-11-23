{{--
    Pastille d'un statut ou d'une priorité, dans sa couleur paramétrée (fond teinté à 10 %).
    on-dark : fond blanc, pour une pastille posée sur un fond sombre (bandeau bleu marine).
--}}
@props(['label', 'color' => null, 'onDark' => false])
@php
    $color = $color ?: '#64748B';
    $background = $onDark ? '#ffffff' : $color.'1a';
@endphp
<span {{ $attributes->class('inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap') }}
    style="color: {{ $color }}; background-color: {{ $background }}">
    <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $color }}"></span>
    {{ $label }}
</span>

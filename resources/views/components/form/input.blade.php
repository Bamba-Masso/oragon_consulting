{{--
    Champ de formulaire avec libellé, valeur précédente (old) et message d'erreur.
    Les attributs HTML supplémentaires (required, placeholder, autocomplete...) sont transmis à l'input.
    - id : identifiant HTML, à préciser quand le même formulaire apparaît plusieurs fois sur la page ;
    - submitted : false pour ignorer l'ancienne saisie et les erreurs (ils appartiennent à un autre formulaire).
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'bag' => 'default',
    'hint' => null,
    'id' => null,
    'submitted' => true,
])
@php
    $inputId = $id ?? $name;
    $error = $submitted ? $errors->getBag($bag)->first($name) : null;
    $currentValue = $submitted ? old($name, $value) : $value;
@endphp
<div>
    <label for="{{ $inputId }}" class="block mb-2 text-sm font-medium text-navy-900">
        {{ $label }}
        @if ($attributes->has('required'))
            <span class="text-gold-600" aria-hidden="true">*</span>
        @endif
    </label>
    <input type="{{ $type }}" id="{{ $inputId }}" name="{{ $name }}" @if ($type !== 'file') value="{{ $currentValue }}" @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
        {{ $attributes->class([
            'block w-full rounded-xl border bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-4 transition',
            'border-red-400 focus:border-red-500 focus:ring-red-500/10' => $error,
            'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15' => ! $error,
        ]) }}>
    @if ($error)
        <p id="{{ $inputId }}-error" class="mt-2 text-xs text-red-600">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-2 text-xs text-slate-400">{{ $hint }}</p>
    @endif
</div>

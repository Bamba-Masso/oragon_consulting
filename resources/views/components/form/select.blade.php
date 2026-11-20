{{--
    Liste déroulante avec libellé, valeur précédente (old) et message d'erreur.
    $options : [valeur => libellé]. Options id et submitted : voir x-form.input.
--}}
@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'bag' => 'default',
    'id' => null,
    'submitted' => true,
])
@php
    $selectId = $id ?? $name;
    $error = $submitted ? $errors->getBag($bag)->first($name) : null;
    $selected = (string) ($submitted ? old($name, $value) : $value);
@endphp
<div>
    <label for="{{ $selectId }}" class="block mb-2 text-sm font-medium text-navy-900">
        {{ $label }}
        @if ($attributes->has('required'))
            <span class="text-gold-600" aria-hidden="true">*</span>
        @endif
    </label>
    <select id="{{ $selectId }}" name="{{ $name }}"
        @if ($error) aria-invalid="true" aria-describedby="{{ $selectId }}-error" @endif
        {{ $attributes->class([
            'block w-full rounded-xl border bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-4 transition',
            'border-red-400 focus:border-red-500 focus:ring-red-500/10' => $error,
            'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15' => ! $error,
        ]) }}>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($error)
        <p id="{{ $selectId }}-error" class="mt-2 text-xs text-red-600">{{ $error }}</p>
    @endif
</div>

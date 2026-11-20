{{--
    Modale de confirmation (remplace le confirm() du navigateur). S'ouvre avec un bouton portant
    data-modal-target et data-modal-toggle égaux à $id. Le message est passé dans le slot.
    variant : « danger » (rouge, ex. désactiver) ou « success » (vert, ex. réactiver).
--}}
@props([
    'id',
    'title',
    'action',
    'method' => 'POST',
    'confirmLabel' => 'Confirmer',
    'variant' => 'danger',
])
@php
    $isDanger = $variant === 'danger';
@endphp

<div id="{{ $id }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-2xl shadow-xl p-6 text-center">
            <span @class([
                'mx-auto flex w-12 h-12 items-center justify-center rounded-full',
                'bg-red-50 text-red-600' => $isDanger,
                'bg-emerald-50 text-emerald-600' => ! $isDanger,
            ])>
                @if ($isDanger)
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                @endif
            </span>

            <h3 class="mt-4 text-base font-semibold text-navy-900">{{ $title }}</h3>
            <div class="mt-2 text-sm text-slate-500">{{ $slot }}</div>

            <form method="POST" action="{{ $action }}" class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-center gap-3">
                @csrf
                @if (! in_array(strtoupper($method), ['GET', 'POST']))
                    @method($method)
                @endif

                <button type="button" data-modal-hide="{{ $id }}"
                    class="inline-flex justify-center px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
                    Annuler
                </button>
                <button type="submit" @class([
                    'inline-flex justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition focus:outline-none focus:ring-4',
                    'bg-red-600 hover:bg-red-700 focus:ring-red-500/25' => $isDanger,
                    'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500/25' => ! $isDanger,
                ])>
                    {{ $confirmLabel }}
                </button>
            </form>
        </div>
    </div>
</div>

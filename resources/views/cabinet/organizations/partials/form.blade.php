{{-- Champs communs à la création et à la modification d'une organisation. $organization peut être null. --}}
@php
    $legalForms = array_combine(App\Models\Organization::LEGAL_FORMS, App\Models\Organization::LEGAL_FORMS);
@endphp

<div class="space-y-8">
    <fieldset class="space-y-5">
        <legend class="text-base font-semibold text-navy-900">Identité</legend>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="sm:col-span-2">
                <x-form.input name="name" label="Raison sociale" :value="$organization?->name" required autofocus />
            </div>
            <x-form.select name="legal_form" label="Forme juridique" :options="$legalForms" :value="$organization?->legal_form" placeholder="Choisir…" required />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <x-form.input name="registration_number" label="Numéro RCCM" :value="$organization?->registration_number" required placeholder="CI-ABJ-2026-B-00000" />
            <x-form.input name="tax_number" label="Numéro de compte contribuable (NCC)" :value="$organization?->tax_number" required />
        </div>
    </fieldset>

    <fieldset class="space-y-5 pt-8 border-t border-slate-100">
        <legend class="text-base font-semibold text-navy-900">Coordonnées</legend>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <x-form.input type="email" name="email" label="E-mail" :value="$organization?->email" required autocomplete="email" placeholder="contact@entreprise.ci" />
            <x-form.input type="tel" name="phone" label="Téléphone" :value="$organization?->phone" required autocomplete="tel" placeholder="+225 07 00 00 00 00" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="sm:col-span-2">
                <x-form.input name="address" label="Adresse" :value="$organization?->address" required autocomplete="street-address" />
            </div>
            <x-form.input name="city" label="Ville" :value="$organization?->city" required autocomplete="address-level2" placeholder="Abidjan" />
        </div>
    </fieldset>
</div>

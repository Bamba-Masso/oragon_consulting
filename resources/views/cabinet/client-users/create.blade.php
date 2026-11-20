{{--
    Modale de création d'un utilisateur du portail client (incluse dans la liste).
    Le nom d'utilisateur et le mot de passe ne sont pas saisis : ils sont générés et envoyés par e-mail.
    L'ancienne saisie n'est reprise que si c'est ce formulaire qui a été soumis (pas une modification).
--}}
@php
    $isSubmitted = old('client_user_id') === null;
@endphp
<x-modal-form
    id="create-client-user-modal"
    title="Ajouter un client"
    :action="route('cabinet.client-users.store')"
    submit-label="Ajouter"
    max-width="max-w-2xl"
    :auto-open="$errors->any()"
    enctype="multipart/form-data"
>
    <x-form.select name="organization_id" label="Organisation" :options="$activeOrganizations" :submitted="$isSubmitted"
        :value="$selectedOrganizationId ?? null" placeholder="Choisir une organisation…" required />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form.input name="first_name" label="Prénom" :submitted="$isSubmitted" required autocomplete="off" />
        <x-form.input name="last_name" label="Nom" :submitted="$isSubmitted" required autocomplete="off" />
        <x-form.input type="email" name="email" label="E-mail" :submitted="$isSubmitted" required autocomplete="off" placeholder="prenom.nom@entreprise.ci" />
        <x-form.input type="tel" name="phone" label="Téléphone" :submitted="$isSubmitted" autocomplete="off" placeholder="+225 07 00 00 00 00" />
    </div>

    <x-form.input name="job_title" label="Fonction" :submitted="$isSubmitted" autocomplete="off" placeholder="Ex. : Responsable administratif" />

    <x-form.input name="address" label="Adresse" :submitted="$isSubmitted" autocomplete="off" placeholder="Ex. : Cocody, Rue des Jardins, Abidjan" />

    <x-form.input type="file" name="avatar" label="Photo" :submitted="$isSubmitted" accept="image/jpeg,image/png,image/webp"
        hint="Facultatif — JPG, PNG ou WEBP, 2 Mo maximum."
        class="file:mr-3 file:rounded-lg file:border-0 file:bg-navy-900 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white" />

    <p class="flex items-start gap-2 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
        <svg class="w-4 h-4 shrink-0 text-gold-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
        Le nom d'utilisateur (prenom.nom) et un mot de passe temporaire sont générés automatiquement, puis envoyés avec l'e-mail de connexion à l'adresse saisie.
    </p>
</x-modal-form>

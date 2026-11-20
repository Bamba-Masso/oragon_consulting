{{-- Modale de création d'une organisation cliente (incluse dans la liste). --}}
<x-modal-form
    id="create-organization-modal"
    title="Nouvelle organisation"
    :action="route('cabinet.organizations.store')"
    submit-label="Créer l'organisation"
    max-width="max-w-2xl"
    :auto-open="$errors->any()"
    novalidate
>
    @include('cabinet.organizations.partials.form', ['organization' => null])
</x-modal-form>

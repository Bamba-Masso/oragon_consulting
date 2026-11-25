{{--
    Modale « Demander des pièces » de la fiche du dossier. Attend $folder.
    Erreurs dans le sac « storeDocumentRequest » ; ancienne saisie seulement pour ce formulaire (champ caché form).
--}}
@php
    $isSubmitted = old('form') === 'document-request';
    $descriptionError = $isSubmitted ? $errors->storeDocumentRequest->first('description') : null;
@endphp
<x-modal-form
    id="create-document-request-modal"
    title="Demander des pièces"
    :action="route('cabinet.folders.document-requests.store', $folder)"
    submit-label="Envoyer la demande"
    max-width="max-w-2xl"
    :auto-open="$isSubmitted && $errors->storeDocumentRequest->any()"
>
    <input type="hidden" name="form" value="document-request">

    <p class="text-sm text-slate-500">
        Les utilisateurs de l'entreprise <span class="font-medium text-navy-900">{{ $folder->organization?->name }}</span> recevront la demande par e-mail.
    </p>

    <x-form.input name="title" id="document-request-title" label="Objet" placeholder="Ex. : relevés bancaires du 3e trimestre"
        bag="storeDocumentRequest" :submitted="$isSubmitted" required autocomplete="off" />

    <div>
        <label for="document-request-description" class="block mb-2 text-sm font-medium text-navy-900">
            Pièces attendues <span class="text-gold-600" aria-hidden="true">*</span>
        </label>
        <textarea id="document-request-description" name="description" rows="5" maxlength="5000" required
            placeholder="Listez les pièces dont le cabinet a besoin…"
            @class([
                'block w-full rounded-xl border bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-4 transition',
                'border-red-400 focus:border-red-500 focus:ring-red-500/10' => $descriptionError,
                'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15' => ! $descriptionError,
            ])>{{ $isSubmitted ? old('description') : '' }}</textarea>
        @if ($descriptionError)
            <p class="mt-2 text-xs text-red-600">{{ $descriptionError }}</p>
        @endif
    </div>
</x-modal-form>

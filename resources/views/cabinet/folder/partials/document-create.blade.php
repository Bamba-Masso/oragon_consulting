{{--
    Modale « Ajouter un document » de la fiche du dossier. Attend $folder.
    Erreurs dans le sac « storeDocument » ; ancienne saisie seulement pour ce formulaire (champ caché form).
    Le document ajouté est « À envoyer » jusqu'à son envoi au client.
--}}
@php
    $isSubmitted = old('form') === 'document';
    $fileError = $isSubmitted ? $errors->storeDocument->first('file') : null;
@endphp
<x-modal-form
    id="create-document-modal"
    title="Ajouter un document"
    :action="route('cabinet.folders.documents.store', $folder)"
    submit-label="Ajouter"
    max-width="max-w-2xl"
    :auto-open="$isSubmitted && $errors->storeDocument->any()"
    enctype="multipart/form-data"
>
    <input type="hidden" name="form" value="document">

    <div>
        <label for="document-file" class="block mb-2 text-sm font-medium text-navy-900">
            Fichier <span class="text-gold-600" aria-hidden="true">*</span>
        </label>
        <input type="file" id="document-file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.csv,.zip"
            class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-navy-900 hover:file:bg-slate-200">
        <p class="mt-1 text-xs text-slate-400">PDF, images, Word, Excel, CSV ou ZIP — 20 Mo maximum.</p>
        @if ($fileError)
            <p class="mt-1 text-xs text-red-600">{{ $fileError }}</p>
        @endif
    </div>

    <x-form.input name="name" id="document-name" label="Nom du document" placeholder="Ex. : reçu de dépôt de la déclaration TVA"
        bag="storeDocument" :submitted="$isSubmitted" required autocomplete="off" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form.input name="category" id="document-category" label="Catégorie" placeholder="Ex. : reçu, bilan, attestation…"
            bag="storeDocument" :submitted="$isSubmitted" required autocomplete="off" />
        <x-form.input name="period" id="document-period" label="Période" placeholder="Ex. : 2026, T3 2026"
            bag="storeDocument" :submitted="$isSubmitted" autocomplete="off" />
    </div>

    <p class="text-xs text-slate-500">Le document sera « À envoyer » : le client ne le verra qu'une fois que vous l'aurez envoyé.</p>
</x-modal-form>

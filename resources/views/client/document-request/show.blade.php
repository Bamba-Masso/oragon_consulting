<x-layouts.client :title="$documentRequest->reference">
    @php
        $reasonError = $errors->rejectDocumentRequest->first('rejection_reason');
    @endphp

    <div class="max-w-7xl mx-auto space-y-6">

        <x-document-request.summary :document-request="$documentRequest"
            :back-url="route('client.document-requests.index')" back-label="Pièces demandées">
            <x-slot:actions>
                @can('respond', $documentRequest)
                    <button type="button" data-modal-target="reject-request-modal" data-modal-toggle="reject-request-modal"
                        class="inline-flex items-center px-4 py-2 rounded-xl border border-red-100 bg-white text-sm font-medium text-red-600 hover:bg-red-50 transition">
                        Rejeter
                    </button>
                    <button type="button" data-modal-target="accept-request-modal" data-modal-toggle="accept-request-modal"
                        class="inline-flex items-center px-4 py-2 rounded-xl bg-navy-900 hover:bg-navy-800 text-sm font-medium text-white transition">
                        Marquer comme reçue
                    </button>
                @endcan
            </x-slot:actions>
        </x-document-request.summary>

        <x-document-request.thread :document-request="$documentRequest"
            download-route="client.document-requests.attachments.download"
            :send-action="auth('client')->user()->can('sendMessage', $documentRequest) ? route('client.document-requests.messages.store', $documentRequest) : null" />
    </div>

    @can('respond', $documentRequest)
        <x-confirm-modal id="accept-request-modal" title="Marquer la demande comme reçue ?" variant="success"
            :action="route('client.document-requests.accept', $documentRequest)" method="PATCH" confirm-label="Marquer comme reçue">
            Vous prenez en charge cette demande : la discussion avec le cabinet s'ouvrira pour lui transmettre vos pièces.
        </x-confirm-modal>

        <x-modal-form id="reject-request-modal" title="Rejeter la demande" submit-label="Rejeter la demande"
            :action="route('client.document-requests.reject', $documentRequest)" method="PATCH" max-width="max-w-lg"
            :auto-open="$errors->rejectDocumentRequest->any()">
            <p class="text-sm text-slate-500">Une fois rejetée, la demande ne pourra plus être consultée ni traitée.</p>
            <div>
                <label for="rejection-reason" class="block mb-2 text-sm font-medium text-navy-900">Motif du rejet</label>
                <textarea id="rejection-reason" name="rejection_reason" rows="4" maxlength="2000" required
                    @class([
                        'block w-full rounded-xl border bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-4 transition',
                        'border-red-400 focus:border-red-500 focus:ring-red-500/10' => $reasonError,
                        'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15' => ! $reasonError,
                    ])>{{ old('rejection_reason') }}</textarea>
                @if ($reasonError)
                    <p class="mt-2 text-xs text-red-600">{{ $reasonError }}</p>
                @endif
            </div>
        </x-modal-form>
    @endcan
</x-layouts.client>

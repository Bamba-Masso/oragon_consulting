<x-layouts.cabinet :title="$documentRequest->reference">
    <div class="max-w-7xl mx-auto space-y-6">

        <x-document-request.summary :document-request="$documentRequest"
            :back-url="route('cabinet.document-requests.index')" back-label="Demandes de pièces">
            <x-slot:actions>
                @can('remind', $documentRequest)
                    <button type="button" data-modal-target="remind-request-modal" data-modal-toggle="remind-request-modal"
                        class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-navy-900 hover:bg-slate-50 transition">
                        Relancer
                    </button>
                @endcan
                @can('close', $documentRequest)
                    <button type="button" data-modal-target="close-request-modal" data-modal-toggle="close-request-modal"
                        class="inline-flex items-center px-4 py-2 rounded-xl bg-navy-900 hover:bg-navy-800 text-sm font-medium text-white transition">
                        Clôturer
                    </button>
                @endcan
            </x-slot:actions>
        </x-document-request.summary>

        @unless ($documentRequest->hasStatus(App\Models\DocumentRequest::REJECTED))
            <x-document-request.thread :document-request="$documentRequest"
                download-route="cabinet.document-requests.attachments.download"
                :send-action="auth('cabinet')->user()->can('sendMessage', $documentRequest) ? route('cabinet.document-requests.messages.store', $documentRequest) : null" />
        @endunless
    </div>

    @can('remind', $documentRequest)
        <x-confirm-modal id="remind-request-modal" title="Relancer l'entreprise ?" variant="success"
            :action="route('cabinet.document-requests.remind', $documentRequest)" confirm-label="Relancer">
            Les utilisateurs de l'entreprise recevront à nouveau l'e-mail de la demande.
        </x-confirm-modal>
    @endcan

    @can('close', $documentRequest)
        <x-confirm-modal id="close-request-modal" title="Clôturer la demande ?"
            :action="route('cabinet.document-requests.close', $documentRequest)" method="PATCH" confirm-label="Clôturer">
            La discussion sera terminée : plus aucune action ne sera possible sur cette demande.
        </x-confirm-modal>
    @endcan
</x-layouts.cabinet>

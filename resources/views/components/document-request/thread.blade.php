{{--
    Discussion d'une demande de pièces (messages et pièces jointes), commune au cabinet et au client.
    - downloadRoute : nom de la route de téléchargement d'une pièce jointe de l'espace courant ;
    - sendAction : URL d'envoi d'un message, ou null quand la discussion est en lecture seule.
    Elle ne s'ouvre qu'une fois la demande marquée reçue par l'entreprise.
--}}
@props(['documentRequest', 'downloadRoute', 'sendAction' => null])
@use('App\Models\ClientUser')
@use('App\Models\DocumentRequest')
@php
    $isOpen = $documentRequest->hasStatus(DocumentRequest::RECEIVED) || $documentRequest->hasStatus(DocumentRequest::CLOSED);
    $messageErrors = $errors->getBag('storeMessage');
    $attachmentError = $messageErrors->first('attachments') ?: collect($messageErrors->get('attachments.*'))->flatten()->first();
@endphp

<section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
    <header class="px-6 py-4 border-b border-slate-100">
        <h3 class="text-base font-semibold text-navy-900">Discussion</h3>
    </header>

    @if (! $isOpen)
        <p class="px-6 py-6 text-center text-sm text-slate-500">
            La discussion s'ouvrira dès que l'entreprise aura marqué la demande comme reçue.
        </p>
    @else
        @forelse ($documentRequest->messages as $message)
            @php
                $fromClient = $message->sender instanceof ClientUser;
            @endphp
            <div class="flex gap-3 px-6 py-4 border-b border-slate-100">
                <span @class([
                    'w-8 h-8 shrink-0 flex items-center justify-center rounded-full text-xs font-semibold',
                    'bg-gold-500/15 text-gold-700' => $fromClient,
                    'bg-navy-900 text-gold-500' => ! $fromClient,
                ])>{{ $message->sender?->initials }}</span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-500">
                        <span class="font-medium text-navy-900">{{ $message->sender?->full_name ?? 'Compte supprimé' }}</span>
                        · {{ $fromClient ? 'Entreprise' : 'Cabinet' }}
                        · {{ $message->created_at->format('d/m/Y à H:i') }}
                    </p>
                    @if ($message->message)
                        <p class="mt-1 text-sm text-slate-700 whitespace-pre-line break-words">{{ $message->message }}</p>
                    @endif

                    @if ($message->attachments->isNotEmpty())
                        <ul class="mt-2 flex flex-wrap gap-2">
                            @foreach ($message->attachments as $attachment)
                                <li>
                                    <a href="{{ route($downloadRoute, [$documentRequest, $attachment]) }}"
                                        class="inline-flex items-center gap-1.5 max-w-xs rounded-lg ring-1 ring-slate-200 px-2.5 py-1.5 text-xs text-navy-900 hover:bg-slate-50 transition">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                                        <span class="truncate">{{ $attachment->file_name }}</span>
                                        <span class="shrink-0 text-slate-400">{{ Illuminate\Support\Number::fileSize($attachment->file_size) }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @empty
            <p class="px-6 py-6 text-center text-sm text-slate-500 border-b border-slate-100">Aucun message pour le moment.</p>
        @endforelse

        @if ($sendAction)
            <form method="POST" action="{{ $sendAction }}" enctype="multipart/form-data" class="px-6 py-4 space-y-3">
                @csrf
                <div>
                    <label for="message-content" class="sr-only">Message</label>
                    <textarea id="message-content" name="message" rows="3" maxlength="5000" placeholder="Écrire un message…"
                        @class([
                            'block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-4 transition',
                            'border-red-400 focus:border-red-500 focus:ring-red-500/10' => $messageErrors->has('message'),
                            'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15' => ! $messageErrors->has('message'),
                        ])>{{ old('message') }}</textarea>
                    @if ($messageErrors->has('message'))
                        <p class="mt-1.5 text-xs text-red-600">{{ $messageErrors->first('message') }}</p>
                    @endif
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="min-w-0">
                        <label for="message-attachments" class="sr-only">Pièces jointes</label>
                        <input type="file" id="message-attachments" name="attachments[]" multiple
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.csv,.zip"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-navy-900 hover:file:bg-slate-200">
                        <p class="mt-1 text-[11px] text-slate-400">PDF, images, Word, Excel, CSV ou ZIP — 10 Mo maximum par fichier, 10 fichiers au plus.</p>
                        @if ($attachmentError)
                            <p class="mt-1 text-xs text-red-600">{{ $attachmentError }}</p>
                        @endif
                    </div>
                    <button type="submit"
                        class="shrink-0 inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-navy-900 hover:bg-navy-800 text-xs font-semibold text-white transition">
                        Envoyer
                    </button>
                </div>
            </form>
        @elseif ($documentRequest->hasStatus(DocumentRequest::CLOSED))
            <p class="px-6 py-4 text-center text-xs text-slate-400">Demande clôturée : la discussion est en lecture seule.</p>
        @endif
    @endif
</section>

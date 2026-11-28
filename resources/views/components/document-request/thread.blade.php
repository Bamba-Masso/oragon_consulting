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
    <header class="flex items-center justify-between gap-3 px-6 py-4 bg-linear-to-r from-slate-50 to-slate-100 border-b-2 border-navy-900">
        <h3 class="flex items-center gap-2 text-base font-semibold text-navy-900">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z"/></svg>
            Discussion
        </h3>
        @if ($isOpen && $documentRequest->messages->isNotEmpty())
            <span class="min-w-6 rounded-md bg-navy-900 px-1.5 py-0.5 text-center text-xs font-semibold text-white">{{ $documentRequest->messages->count() }}</span>
        @endif
    </header>

    @if (! $isOpen)
        <p class="px-6 py-6 text-center text-sm text-slate-500">
            La discussion s'ouvrira dès que l'entreprise aura marqué la demande comme reçue.
        </p>
    @else
        {{-- Fil des messages, regroupés par jour, sur fond pointillé ; hauteur limitée, défilement interne --}}
        <div id="message-thread" class="max-h-64 overflow-y-auto overscroll-contain bg-slate-100 px-4 sm:px-6 py-5 space-y-4"
            style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 16px 16px;">
            @forelse ($documentRequest->messages->groupBy(fn ($message) => $message->created_at->toDateString()) as $messagesOfTheDay)
                <p class="inline-block rounded-full bg-white px-4 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 shadow-sm">
                    {{ $messagesOfTheDay->first()->created_at->locale('fr')->translatedFormat('j F Y') }}
                </p>

                @foreach ($messagesOfTheDay as $message)
                    @php
                        $fromClient = $message->sender instanceof ClientUser;
                    @endphp
                    <div class="w-fit min-w-48 max-w-[85%] sm:max-w-[70%] rounded-xl bg-white px-4 py-2.5 shadow-sm">
                        <p class="text-xs">
                            <span class="font-bold uppercase text-navy-900">{{ $message->sender?->full_name ?? 'Compte supprimé' }}</span>
                            <span class="text-slate-400">· {{ $fromClient ? 'Entreprise' : 'Cabinet' }}</span>
                        </p>
                        @if ($message->message)
                            <p class="mt-1 text-sm text-slate-800 whitespace-pre-line break-words">{{ $message->message }}</p>
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

                        <p class="mt-1 text-right text-xs text-slate-400">{{ $message->created_at->format('H:i') }}</p>
                    </div>
                @endforeach
            @empty
                <p class="py-4 text-center text-sm text-slate-500">Aucun message pour le moment.</p>
            @endforelse
        </div>

        {{-- À l'ouverture, le fil s'affiche sur les derniers messages --}}
        <script>
            (function () {
                const thread = document.getElementById('message-thread');
                thread.scrollTop = thread.scrollHeight;
            })();
        </script>

        @if ($sendAction)
            <form method="POST" action="{{ $sendAction }}" enctype="multipart/form-data" class="px-6 py-4 space-y-3 border-t border-slate-200">
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
            <p class="px-6 py-4 text-center text-xs text-slate-400 border-t border-slate-200">Demande clôturée : la discussion est en lecture seule.</p>
        @endif
    @endif
</section>

<x-layouts.cabinet :title="$task->title">
    @php
        $canManageChecklist = auth('cabinet')->user()->can('manageChecklist', $task);
        $items = $task->checklistItems;
        $checklistErrors = $errors->getBag('storeChecklist');
    @endphp

    <div class="max-w-7xl mx-auto space-y-6">

        <a href="{{ route('folder.tasks') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-navy-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            Mes tâches
        </a>

        <x-flash />

        {{-- En-tête et informations --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            <div class="bg-linear-to-r from-navy-900 to-navy-800 px-6 py-6 sm:px-8 text-white">
                <p class="font-mono text-xs text-white/60">{{ $task->folder->reference }}</p>
                <h2 class="mt-1 text-lg font-bold leading-tight break-words">{{ $task->title }}</h2>
                <p class="mt-1 text-sm text-white/70">{{ $task->folder->title }} · {{ $task->folder->organization?->name ?? '—' }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-status-badge :label="$task->status->name" :color="$task->status->color" on-dark />
                    @if ($task->priority)
                        <span class="rounded-md bg-white/15 px-2 py-0.5 text-xs font-semibold">Priorité {{ mb_strtolower($task->priority->label) }}</span>
                    @endif
                </div>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5 px-6 py-6 sm:px-8 text-sm">
                <div class="min-w-0">
                    <dt class="text-xs text-slate-400">Dossier</dt>
                    <dd class="mt-1 font-medium text-navy-900">
                        @can('view', $task->folder)
                            <a href="{{ route('cabinet.folders.show', $task->folder) }}" class="hover:text-gold-700 transition">{{ $task->folder->reference }}</a>
                        @else
                            {{ $task->folder->reference }}
                        @endcan
                    </dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-xs text-slate-400">Échéance</dt>
                    <dd @class([
                        'mt-1 font-medium',
                        'text-red-600' => $task->due_date?->isPast() && ! $task->completed_at,
                        'text-navy-900' => ! ($task->due_date?->isPast() && ! $task->completed_at),
                    ])>{{ $task->due_date?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-xs text-slate-400">Créée le</dt>
                    <dd class="mt-1 font-medium text-navy-900">{{ $task->created_at->format('d/m/Y') }}</dd>
                </div>
                @if ($task->description)
                    <div class="sm:col-span-2 lg:col-span-3 min-w-0">
                        <dt class="text-xs text-slate-400">Description</dt>
                        <dd class="mt-1 text-slate-700 whitespace-pre-line break-words">{{ $task->description }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        {{-- Checklist --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            <header class="flex items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                <h3 class="text-base font-semibold text-navy-900">Checklist</h3>
                @if ($items->isNotEmpty())
                    <span class="text-xs text-slate-500">{{ $items->where('is_completed', true)->count() }}/{{ $items->count() }} terminé(s)</span>
                @endif
            </header>

            @forelse ($items as $item)
                <div class="flex items-center gap-3 px-6 py-2.5 border-b border-slate-100 text-sm">
                    @if ($canManageChecklist)
                        <form method="POST" action="{{ route('cabinet.tasks.checklist-items.completion', [$task, $item]) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                title="{{ $item->is_completed ? 'Marquer comme non terminé' : 'Marquer comme terminé' }}"
                                aria-label="{{ $item->is_completed ? 'Marquer comme non terminé' : 'Marquer comme terminé' }} : {{ $item->label }}"
                                @class([
                                    'w-5 h-5 flex items-center justify-center rounded-md border transition',
                                    'bg-emerald-600 border-emerald-600 text-white' => $item->is_completed,
                                    'border-slate-300 text-transparent hover:border-emerald-600' => ! $item->is_completed,
                                ])>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </button>
                        </form>
                    @else
                        <span @class([
                            'w-5 h-5 flex items-center justify-center rounded-md border',
                            'bg-emerald-600 border-emerald-600 text-white' => $item->is_completed,
                            'border-slate-300 text-transparent' => ! $item->is_completed,
                        ])>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        </span>
                    @endif

                    <span @class(['flex-1 min-w-0 break-words', 'text-slate-400 line-through' => $item->is_completed, 'text-slate-700' => ! $item->is_completed])>{{ $item->label }}</span>

                    @if ($canManageChecklist)
                        <button type="button" data-modal-target="delete-checklist-item-{{ $item->id }}" data-modal-toggle="delete-checklist-item-{{ $item->id }}"
                            title="Supprimer" aria-label="Supprimer le point {{ $item->label }}"
                            class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    @endif
                </div>
            @empty
                <p class="px-6 py-6 text-center text-sm text-slate-500 border-b border-slate-100">Aucun point dans la checklist.</p>
            @endforelse

            @if ($canManageChecklist)
                <form method="POST" action="{{ route('cabinet.tasks.checklist-items.store', $task) }}" class="px-6 py-4">
                    @csrf
                    <div class="flex gap-2">
                        <label for="checklist-label" class="sr-only">Nouveau point de la checklist</label>
                        <input type="text" id="checklist-label" name="label" maxlength="255" value="{{ old('label') }}" placeholder="Nouveau point de la checklist…"
                            class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition">
                        <button type="submit"
                            class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-navy-900 hover:bg-navy-800 text-xs font-semibold text-white transition">
                            <svg class="w-3.5 h-3.5 text-gold-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Ajouter
                        </button>
                    </div>
                    @if ($checklistErrors->has('label'))
                        <p class="mt-1.5 text-xs text-red-600">{{ $checklistErrors->first('label') }}</p>
                    @endif
                </form>
            @endif
        </section>

        {{-- Commentaires internes (jamais visibles du client) --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            <header class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-base font-semibold text-navy-900">Commentaires</h3>
            </header>

            @forelse ($task->comments as $comment)
                <div class="flex gap-3 px-6 py-4 border-b border-slate-100">
                    <span class="w-8 h-8 shrink-0 flex items-center justify-center rounded-full bg-navy-900 text-xs font-semibold text-gold-500">{{ $comment->author?->initials }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-500">
                            <span class="font-medium text-navy-900">{{ $comment->author?->full_name ?? 'Collaborateur' }}</span>
                            · {{ $comment->created_at->format('d/m/Y à H:i') }}
                            @if ($comment->updated_at->gt($comment->created_at))
                                · modifié
                            @endif
                        </p>
                        <p class="mt-1 text-sm text-slate-700 whitespace-pre-line break-words">{{ $comment->content }}</p>
                    </div>

                    @canany(['update', 'delete'], $comment)
                        <div class="flex items-start gap-0.5 shrink-0">
                            @can('update', $comment)
                                <button type="button" data-modal-target="edit-comment-{{ $comment->id }}" data-modal-toggle="edit-comment-{{ $comment->id }}"
                                    title="Modifier" aria-label="Modifier le commentaire"
                                    class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-navy-900 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                </button>
                            @endcan
                            @can('delete', $comment)
                                <button type="button" data-modal-target="delete-comment-{{ $comment->id }}" data-modal-toggle="delete-comment-{{ $comment->id }}"
                                    title="Supprimer" aria-label="Supprimer le commentaire"
                                    class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </button>
                            @endcan
                        </div>
                    @endcanany
                </div>
            @empty
                <p class="px-6 py-6 text-center text-sm text-slate-500 border-b border-slate-100">Aucun commentaire.</p>
            @endforelse

            @can('comment', $task)
                @php
                    $commentError = old('form') === 'comment' ? $errors->storeComment->first('content') : null;
                @endphp
                <form method="POST" action="{{ route('cabinet.tasks.comments.store', $task) }}" class="px-6 py-4">
                    @csrf
                    <input type="hidden" name="form" value="comment">
                    <label for="comment-content" class="sr-only">Nouveau commentaire</label>
                    <textarea id="comment-content" name="content" rows="3" maxlength="5000" placeholder="Écrire un commentaire…"
                        @class([
                            'block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-4 transition',
                            'border-red-400 focus:border-red-500 focus:ring-red-500/10' => $commentError,
                            'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15' => ! $commentError,
                        ])>{{ old('form') === 'comment' ? old('content') : '' }}</textarea>
                    @if ($commentError)
                        <p class="mt-1.5 text-xs text-red-600">{{ $commentError }}</p>
                    @endif
                    <div class="mt-2 flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-navy-900 hover:bg-navy-800 text-xs font-semibold text-white transition">
                            Commenter
                        </button>
                    </div>
                </form>
            @endcan
        </section>
    </div>

    {{-- Confirmations de suppression des points de la checklist --}}
    @if ($canManageChecklist)
        @foreach ($items as $item)
            <x-confirm-modal :id="'delete-checklist-item-'.$item->id" title="Supprimer ce point ?"
                :action="route('cabinet.tasks.checklist-items.destroy', [$task, $item])" method="DELETE" confirm-label="Supprimer">
                « {{ $item->label }} » sera retiré de la checklist de la tâche.
            </x-confirm-modal>
        @endforeach
    @endif

    {{-- Modification et suppression des commentaires, réservées à leur auteur --}}
    @foreach ($task->comments as $comment)
        @can('update', $comment)
            @php
                $isSubmitted = old('form') === 'comment-edit' && old('comment_id') === $comment->id;
                $editError = $isSubmitted ? $errors->updateComment->first('content') : null;
            @endphp
            <x-modal-form :id="'edit-comment-'.$comment->id" title="Modifier le commentaire"
                :action="route('cabinet.tasks.comments.update', [$task, $comment])" method="PUT" max-width="max-w-lg"
                :auto-open="$isSubmitted && $errors->updateComment->any()">
                <input type="hidden" name="form" value="comment-edit">
                <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                <div>
                    <label for="edit-comment-{{ $comment->id }}-content" class="sr-only">Commentaire</label>
                    <textarea id="edit-comment-{{ $comment->id }}-content" name="content" rows="4" maxlength="5000"
                        @class([
                            'block w-full rounded-xl border bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-4 transition',
                            'border-red-400 focus:border-red-500 focus:ring-red-500/10' => $editError,
                            'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15' => ! $editError,
                        ])>{{ $isSubmitted ? old('content') : $comment->content }}</textarea>
                    @if ($editError)
                        <p class="mt-2 text-xs text-red-600">{{ $editError }}</p>
                    @endif
                </div>
            </x-modal-form>
        @endcan
        @can('delete', $comment)
            <x-confirm-modal :id="'delete-comment-'.$comment->id" title="Supprimer ce commentaire ?"
                :action="route('cabinet.tasks.comments.destroy', [$task, $comment])" method="DELETE" confirm-label="Supprimer">
                Le commentaire sera définitivement supprimé.
            </x-confirm-modal>
        @endcan
    @endforeach
</x-layouts.cabinet>

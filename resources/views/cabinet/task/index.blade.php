<x-layouts.cabinet title="Mes tâches">
    <div class="space-y-6">

        <x-flash />

        {{-- Recherche seule, sur toute la largeur --}}
        <form method="GET" action="{{ route('folder.tasks') }}" class="flex gap-3 w-full">
            <label for="search" class="sr-only">Rechercher</label>
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Rechercher une tâche…"
                    class="block w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-gold-600 focus:outline-none focus:ring-4 focus:ring-gold-500/15 transition">
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium text-navy-900 hover:bg-slate-50 transition">
                Rechercher
            </button>
        </form>

        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 overflow-hidden">
            @if ($tasks->isEmpty())
                <div class="px-6 py-16 text-center">
                    <p class="text-sm font-medium text-navy-900">Aucune tâche</p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $search !== '' ? 'Modifiez votre recherche.' : 'Les tâches qui vous sont confiées apparaîtront ici.' }}
                    </p>
                </div>
            @else
                <table class="w-full table-fixed text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-2.5">Tâche</th>
                            <th scope="col" class="hidden sm:table-cell w-32 px-3 py-2.5">Priorité</th>
                            <th scope="col" class="hidden md:table-cell w-32 px-3 py-2.5">Échéance</th>
                            <th scope="col" class="w-36 px-3 py-2.5">Statut</th>
                            <th scope="col" class="w-36 px-5 py-2.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tasks as $task)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-3">
                                    <a href="{{ route('folder.tasks.show', $task) }}" class="block font-medium text-navy-900 truncate hover:text-gold-700 transition">{{ $task->title }}</a>
                                </td>
                                <td class="hidden sm:table-cell px-3 py-3">
                                    @if ($task->priority)
                                        <x-status-badge :label="$task->priority->label" :color="$task->priority->color" />
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="hidden md:table-cell px-3 py-3">
                                    @if ($task->due_date)
                                        <span @class([
                                            'font-semibold text-red-600' => $task->due_date->isPast() && ! $task->completed_at,
                                            'text-slate-600' => ! ($task->due_date->isPast() && ! $task->completed_at),
                                        ])>{{ $task->due_date->format('d/m/Y') }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <x-status-badge :label="$task->status->name" :color="$task->status->color" />
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        @can('start', $task)
                                            <form method="POST" action="{{ route('cabinet.tasks.start', $task) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" title="Démarrer" aria-label="Démarrer la tâche {{ $task->title }}"
                                                    class="p-2 rounded-lg text-slate-400 hover:bg-blue-50 hover:text-blue-600 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg>
                                                </button>
                                            </form>
                                        @endcan
                                        @can('complete', $task)
                                            <form method="POST" action="{{ route('cabinet.tasks.complete', $task) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" title="Terminer" aria-label="Terminer la tâche {{ $task->title }}"
                                                    class="p-2 rounded-lg text-slate-400 hover:bg-emerald-50 hover:text-emerald-600 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                </button>
                                            </form>
                                        @endcan
                                        @can('cancel', $task)
                                            <form method="POST" action="{{ route('cabinet.tasks.cancel', $task) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" title="Annuler" aria-label="Annuler la tâche {{ $task->title }}"
                                                    class="p-2 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                </button>
                                            </form>
                                        @endcan
                                        <a href="{{ route('folder.tasks.show', $task) }}" title="Voir" aria-label="Voir la tâche {{ $task->title }}"
                                            class="p-2 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-navy-900 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{ $tasks->links() }}
    </div>
</x-layouts.cabinet>

<x-layouts.cabinet title="Modifier l'organisation">
    <div class="max-w-3xl mx-auto space-y-6">

        <a href="{{ route('cabinet.organizations.show', $organization) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-navy-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            {{ $organization->name }}
        </a>

        <form method="POST" action="{{ route('cabinet.organizations.update', $organization) }}" class="bg-white rounded-2xl ring-1 ring-slate-200/70 p-6 sm:p-8" novalidate>
            @csrf
            @method('PUT')

            @include('cabinet.organizations.partials.form', ['organization' => $organization])

            <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <a href="{{ route('cabinet.organizations.show', $organization) }}" class="inline-flex justify-center px-5 py-2.5 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50 transition">Annuler</a>
                <button type="submit" class="inline-flex justify-center px-5 py-2.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold transition focus:outline-none focus:ring-4 focus:ring-gold-500/25">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</x-layouts.cabinet>

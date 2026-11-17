<x-layouts.cabinet title="Tableau de bord">
    <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 p-8">
        <h2 class="text-2xl font-bold tracking-tight text-navy-900">
            Bienvenue, {{ auth('cabinet')->user()->first_name ?? auth('cabinet')->user()->username }}
        </h2>
        <p class="mt-2 text-sm text-slate-500">Les indicateurs du cabinet s'afficheront ici.</p>
    </div>
</x-layouts.cabinet>

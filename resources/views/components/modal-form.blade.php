@props([
    'id',
    'title',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
    'maxWidth' => 'max-w-md', // Possibilité de passer 'max-w-lg' ou 'max-w-2xl' pour les grands formulaires
    'autoOpen' => false, // true : la modale s'ouvre au chargement (ex. après une erreur de validation)
])

<div id="{{ $id }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full {{ $maxWidth }} max-h-full">
        <!-- Contenu de la modale -->
        <div class="relative bg-white border border-default rounded-base shadow-xl p-4 md:p-6">
            
            <!-- En-tête de la modale -->
            <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                <h3 class="text-lg font-medium text-heading">
                    {{ $title }}
                </h3>
                <button type="button" class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center" data-modal-hide="{{ $id }}">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
                    </svg>
                    <span class="sr-only">Fermer</span>
                </button>
            </div>

            <!-- Corps du formulaire -->
            <form action="{{ $action }}" method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}" {{ $attributes->merge(['class' => 'pt-4 md:pt-6']) }}>
                @csrf
                @if(!in_array(strtoupper($method), ['GET', 'POST']))
                    @method($method)
                @endif

                <div class="space-y-4">
                    {{ $slot }}
                </div>

                <!-- Pied de formulaire (Actions) -->
                <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-default">
                    <button type="button" data-modal-hide="{{ $id }}" class="text-heading bg-neutral-secondary-medium border border-default-medium hover:bg-neutral-tertiary focus:ring-4 focus:ring-neutral-tertiary font-medium rounded-base text-sm px-4 py-2.5 focus:outline-none">
                        Annuler
                    </button>
                    <button type="submit" class="text-white bg-brand box-border border border-transparent hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">
                        {{ $submitLabel }}
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

@if ($autoOpen)
    {{--
        Flowbite initialise ses modales sur l'événement « load ». Le setTimeout place l'ouverture
        après tous les gestionnaires « load », donc après cette initialisation.
    --}}
    <script>
        window.addEventListener('load', () => setTimeout(() => {
            window.FlowbiteInstances?.getInstance('Modal', @js($id))?.show();
        }));
    </script>
@endif
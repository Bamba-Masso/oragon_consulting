{{--
    Modale de confirmation pour désactiver ou réactiver le compte d'un collaborateur.
    Incluse sur la liste (une par ligne) et sur la fiche. Attend $collaborator.
    Bouton d'ouverture : data-modal-target / data-modal-toggle = "activation-user-{id}".
--}}
<x-confirm-modal
    :id="'activation-user-'.$collaborator->id"
    :title="$collaborator->is_active ? 'Désactiver le compte ?' : 'Réactiver le compte ?'"
    :action="route('cabinet.users.activation', $collaborator)"
    method="PATCH"
    :confirm-label="$collaborator->is_active ? 'Désactiver' : 'Réactiver'"
    :variant="$collaborator->is_active ? 'danger' : 'success'"
>
    @if ($collaborator->is_active)
        <strong class="font-semibold text-navy-900">{{ $collaborator->full_name }}</strong> ne pourra plus se connecter à l'espace collaborateur.
        Vous pourrez réactiver son compte à tout moment.
    @else
        <strong class="font-semibold text-navy-900">{{ $collaborator->full_name }}</strong> pourra de nouveau se connecter à l'espace collaborateur avec ses identifiants actuels.
    @endif
</x-confirm-modal>

{{--
    Modale de confirmation pour désactiver ou réactiver l'accès d'un utilisateur du portail.
    Incluse sur la liste (une par ligne) et sur la fiche. Attend $clientUser.
    Bouton d'ouverture : data-modal-target / data-modal-toggle = "activation-client-user-{id}".
--}}
<x-confirm-modal
    :id="'activation-client-user-'.$clientUser->id"
    :title="$clientUser->is_active ? 'Désactiver l\'accès ?' : 'Réactiver l\'accès ?'"
    :action="route('cabinet.client-users.activation', $clientUser)"
    method="PATCH"
    :confirm-label="$clientUser->is_active ? 'Désactiver' : 'Réactiver'"
    :variant="$clientUser->is_active ? 'danger' : 'success'"
>
    @if ($clientUser->is_active)
        <strong class="font-semibold text-navy-900">{{ $clientUser->full_name }}</strong> ne pourra plus se connecter à l'espace client.
        Vous pourrez réactiver son accès à tout moment.
    @else
        <strong class="font-semibold text-navy-900">{{ $clientUser->full_name }}</strong> pourra de nouveau se connecter à l'espace client avec ses identifiants actuels.
    @endif
</x-confirm-modal>

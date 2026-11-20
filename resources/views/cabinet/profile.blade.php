<x-layouts.cabinet title="Mon profil">
    @php
        $role = $user->getRoleNames()->first();
    @endphp

    <x-profile-page
        :user="$user"
        :badge="$role ? ucfirst($role) : null"
        note="L'e-mail et le nom d'utilisateur sont gérés par l'administrateur."
        update-route="cabinet.profile.update"
        password-route="cabinet.profile.password" />
</x-layouts.cabinet>

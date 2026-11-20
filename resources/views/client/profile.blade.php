<x-layouts.client title="Mon profil">
    <x-profile-page
        :user="$user"
        :badge="$user->organization?->name"
        note="Pour changer votre e-mail ou votre nom d'utilisateur, contactez le cabinet."
        update-route="client.profile.update"
        password-route="client.profile.password"
        with-job-title />
</x-layouts.client>

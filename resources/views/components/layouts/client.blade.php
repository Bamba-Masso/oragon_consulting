@props(['title' => 'Tableau de bord'])
@php
    $user = auth('client')->user();
@endphp
<x-layouts.dashboard
    :title="$title"
    space="Espace client"
    :user="$user"
    :subtitle="$user->organization?->name"
    logout-route="client.logout"
    profile-route="client.profile"
    notifications-read-route="client.notifications.read"
    notifications-open-route="client.notifications.open">

    <x-slot:navigation>
        <x-nav-link :href="route('client.dashboard')" :active="request()->routeIs('client.dashboard')" icon="home">Tableau de bord</x-nav-link>

        @if ($user->can('view-folders'))
            <x-nav-link :href="route('client.folders.index')" :active="request()->routeIs('client.folders.*')" icon="folder">Mes dossiers</x-nav-link>
        @endif
        @if ($user->can('view-document-requests'))
            <x-nav-link :href="route('client.document-requests.index')" :active="request()->routeIs('client.document-requests.*')" icon="inbox">Pièces demandées</x-nav-link>
        @endif
        @if ($user->can('view-documents'))
            <x-nav-link :href="route('client.documents.index')" :active="request()->routeIs('client.documents.*')" icon="document">Documents</x-nav-link>
        @endif
    </x-slot:navigation>

    {{ $slot }}
</x-layouts.dashboard>

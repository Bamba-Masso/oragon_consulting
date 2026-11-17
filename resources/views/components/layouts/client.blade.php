@props(['title' => 'Tableau de bord'])
@php
    $user = auth('client')->user();
@endphp
<x-layouts.dashboard
    :title="$title"
    space="Espace client"
    :user="$user"
    :subtitle="$user->organization?->name"
    logout-route="client.logout">

    <x-slot:navigation>
        <x-nav-link :href="route('client.dashboard')" :active="request()->routeIs('client.dashboard')" icon="home">Tableau de bord</x-nav-link>

        @if ($user->can('view-folders'))
            <x-nav-link icon="folder">Mes dossiers</x-nav-link>
        @endif
        @if ($user->can('view-document-requests'))
            <x-nav-link icon="inbox">Pièces demandées</x-nav-link>
        @endif
        @if ($user->can('view-documents'))
            <x-nav-link icon="document">Documents</x-nav-link>
        @endif
    </x-slot:navigation>

    {{ $slot }}
</x-layouts.dashboard>

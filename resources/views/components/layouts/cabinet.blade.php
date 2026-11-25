@props(['title' => 'Tableau de bord'])
@php
    $user = auth('cabinet')->user();
    $role = $user->getRoleNames()->first();
@endphp
<x-layouts.dashboard
    :title="$title"
    space="Espace collaborateur"
    :user="$user"
    :subtitle="$role ? ucfirst($role) : null"
    logout-route="cabinet.logout"
    profile-route="cabinet.profile">

    <x-slot:navigation>
        <x-nav-link :href="route('cabinet.dashboard')" :active="request()->routeIs('cabinet.dashboard')" icon="home">Tableau de bord</x-nav-link>

        @if ($user->canAny(['view-folders', 'view-tasks', 'view-document-requests', 'view-documents', 'view-anomalies']))
            <p class="px-3 pt-6 pb-2 text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-400">Production</p>
            @if ($user->can('view-folders'))
                <x-nav-link :href="route('cabinet.folders.index')" :active="request()->routeIs('cabinet.folders.*')" icon="folder">Dossiers</x-nav-link>
            @endif
            @if ($user->can('view-tasks'))
                <x-nav-link :href="route('folder.tasks')" :active="request()->routeIs('folder.tasks', 'folder.tasks.*')" icon="task">Mes tâches</x-nav-link>
            @endif
            @if ($user->can('view-document-requests'))
                <x-nav-link :href="route('cabinet.document-requests.index')" :active="request()->routeIs('cabinet.document-requests.*')" icon="inbox">Demandes de pièces</x-nav-link>
            @endif
            @if ($user->can('view-documents'))
                <x-nav-link :href="route('cabinet.documents.index')" :active="request()->routeIs('cabinet.documents.*')" icon="document">Documents</x-nav-link>
            @endif
            @if ($user->can('view-anomalies'))
                <x-nav-link icon="alert">Anomalies</x-nav-link>
            @endif
        @endif

        @if ($user->canAny(['view-organizations', 'view-client-users']))
            <p class="px-3 pt-6 pb-2 text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-400">Clients</p>
            @if ($user->can('view-organizations'))
                <x-nav-link :href="route('cabinet.organizations.index')" :active="request()->routeIs('cabinet.organizations.*')" icon="building">Organisations</x-nav-link>
            @endif
            @if ($user->can('view-client-users'))
                <x-nav-link :href="route('cabinet.client-users.index')" :active="request()->routeIs('cabinet.client-users.*')" icon="users">Utilisateurs clients</x-nav-link>
            @endif
        @endif

        @if ($user->canAny(['view-users', 'view-roles', 'view-audit']))
            <p class="px-3 pt-6 pb-2 text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-400">Administration</p>
            @if ($user->can('view-users'))
                <x-nav-link :href="route('cabinet.users.index')" :active="request()->routeIs('cabinet.users.*')" icon="users">Collaborateurs</x-nav-link>
            @endif
            @if ($user->can('view-roles'))
                <x-nav-link icon="shield">Rôles et permissions</x-nav-link>
            @endif
            @if ($user->can('view-audit'))
                <x-nav-link icon="clock">Journal d'audit</x-nav-link>
            @endif
        @endif
    </x-slot:navigation>

    {{ $slot }}
</x-layouts.dashboard>

<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Folder;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Gestion des organisations clientes par le cabinet.
 */
class OrganizationController extends Controller
{
    /**
     * Statuts proposés dans le filtre de la liste.
     *
     * @var array<string, string>
     */
    private const STATUS_FILTERS = ['active' => 'Actives', 'inactive' => 'Désactivées'];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Organization::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $organizations = Organization::query()
            ->when($search !== '', fn (Builder $query) => $query->whereAny(
                ['name', 'registration_number', 'tax_number', 'email', 'city'],
                'like',
                "%{$search}%",
            ))
            ->when($status === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->withCount(['clientUsers', 'folders'])
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('cabinet.organizations.index', [
            'organizations' => $organizations,
            'search' => $search,
            'status' => $status,
            'statusFilters' => self::STATUS_FILTERS,
        ]);
    }

    /**
     * Le formulaire est une modale de la liste (vue cabinet.organizations.create).
     */
    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $organization = new Organization($request->validated());
        $organization->created_by = $request->user()->id;
        $organization->updated_by = $request->user()->id;
        $organization->save();

        return to_route('cabinet.organizations.show', $organization)
            ->with('status', 'L\'organisation a été créée.');
    }

    /**
     * Fiche de l'organisation : ses utilisateurs du portail et les dossiers que le collaborateur peut voir.
     */
    public function show(Request $request, Organization $organization): View
    {
        Gate::authorize('view', $organization);

        $organization->load(['clientUsers' => fn ($query) => $query->orderBy('last_name')->orderBy('first_name')]);

        $folders = $organization->folders()
            ->visibleTo($request->user())
            ->with(['status', 'manager'])
            ->latest()
            ->get();

        return view('cabinet.organizations.show', [
            'organization' => $organization,
            'folders' => $folders,
            'hiddenFoldersCount' => $organization->folders()->count() - $folders->count(),
        ]);
    }

    public function edit(Organization $organization): View
    {
        Gate::authorize('update', $organization);

        return view('cabinet.organizations.edit', [
            'organization' => $organization,
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $organization->fill($request->validated());
        $organization->updated_by = $request->user()->id;
        $organization->save();

        return to_route('cabinet.organizations.show', $organization)
            ->with('status', 'Les informations de l\'organisation ont été enregistrées.');
    }

    /**
     * Désactive ou réactive l'organisation. Désactivée, ses utilisateurs ne peuvent plus se connecter.
     */
    public function toggleActivation(Request $request, Organization $organization): RedirectResponse
    {
        Gate::authorize($organization->is_active ? 'lock' : 'unlock', $organization);

        $organization->is_active = ! $organization->is_active;
        $organization->updated_by = $request->user()->id;
        $organization->save();

        return back()->with('status', $organization->is_active
            ? 'L\'organisation a été réactivée : ses utilisateurs peuvent de nouveau se connecter.'
            : 'L\'organisation a été désactivée : ses utilisateurs ne peuvent plus se connecter.');
    }

    /**
     * Suppression logique, uniquement pour une organisation sans dossier :
     * une organisation qui a un historique se désactive.
     */
    public function destroy(Organization $organization): RedirectResponse
    {
        Gate::authorize('delete', $organization);

        if (Folder::withTrashed()->whereBelongsTo($organization)->exists()) {
            return back()->with('error', 'Cette organisation a des dossiers : désactivez-la plutôt que de la supprimer.');
        }

        $organization->delete();

        return to_route('cabinet.organizations.index')
            ->with('status', 'L\'organisation a été supprimée.');
    }
}

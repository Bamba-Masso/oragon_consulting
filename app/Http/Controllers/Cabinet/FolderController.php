<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\Priority;
use App\Models\Status;
use App\Models\StatusType;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FolderController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Folder::class);

        $search = $request->string('search')->trim()->toString();
        $statusId = $request->string('status')->toString();
        $priorityId = $request->string('priority')->toString();
        $organizationId = $request->string('organization')->toString();

        $folders = Folder::query()
            ->visibleTo($request->user())
            ->with(['organization', 'status', 'priority', 'managers'])
            ->withCount([
                'tasks as active_tasks_count' => fn (Builder $query) => $query->whereRelation('status', 'code', '!=', Task::CANCELLED),
                'tasks as completed_tasks_count' => fn (Builder $query) => $query->whereRelation('status', 'code', Task::COMPLETED),
            ])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereAny(['reference', 'title', 'mission_type', 'period'], 'like', "%{$search}%")
                ->orWhereRelation('organization', 'name', 'like', "%{$search}%")))
            ->when($statusId !== '', fn (Builder $query) => $query->where('status_id', $statusId))
            ->when($priorityId !== '', fn (Builder $query) => $query->where('priority_id', $priorityId))
            ->when($organizationId !== '', fn (Builder $query) => $query->where('organization_id', $organizationId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('cabinet.folder.index', [
            'folders' => $folders,
            'statuses' => $this->folderStatuses()->pluck('name', 'id'),
            'priorities' => $this->priorities(),
            'organizations' => Organization::query()->orderBy('name')->pluck('name', 'id'),
            'activeOrganizations' => Organization::query()->active()->orderBy('name')->pluck('name', 'id'),
            'collaborators' => $this->collaborators(),
            'missionTypes' => Folder::MISSION_TYPES,
            'search' => $search,
            'statusId' => $statusId,
            'priorityId' => $priorityId,
            'organizationId' => $organizationId,
        ]);
    }

    public function store(StoreFolderRequest $request): RedirectResponse
    {
        $author = $request->user();

        $folder = DB::transaction(function () use ($request, $author): Folder {
            $folder = new Folder(Arr::except($request->validated(), ['manager_id', 'assignee_ids']));
            $folder->reference = Folder::nextReference();
            $folder->status_id = $this->folderStatuses()->firstWhere('code', Folder::INITIAL_STATUS)->id;
            $folder->created_by = $author->id;
            $folder->updated_by = $author->id;
            $folder->save();

            $folder->statusChanges()->create([
                'to_status_id' => $folder->status_id,
                'changed_by' => $author->id,
                'comment' => 'Ouverture du dossier',
            ]);

            $folder->syncTeam(
                [...$request->validated('assignee_ids', []), $author->id],
                $request->validated('manager_id'),
                $author,
            );

            return $folder;
        });

        $this->notifyTeamChanges($folder, collect(), null, $author);

        return to_route('cabinet.folders.show', $folder)
            ->with('status', "Le dossier {$folder->reference} a été ouvert.");
    }

    /**
     * Fiche du dossier : informations, collaborateurs affectés, tâches et historique des statuts.
     */
    public function show(Folder $folder): View
    {
        Gate::authorize('view', $folder);

        $folder->load([
            'organization',
            'status',
            'priority',
            'creator',
            'assignees' => fn ($query) => $query->with('roles')->orderBy('last_name')->orderBy('first_name'),
            'managers',
            'tasks' => fn ($query) => $query->with(['status', 'assignee.roles'])->orderBy('due_date'),
            'statusChanges' => fn ($query) => $query->with(['fromStatus', 'toStatus', 'author']),
            'documents' => fn ($query) => $query->latest(),
        ]);

        // Les droits sur chaque document passent par son dossier : inutile de le recharger.
        $folder->documents->each->setRelation('folder', $folder);

        return view('cabinet.folder.show', [
            'folder' => $folder,
            'priorities' => $this->priorities(),
            'collaborators' => $this->collaborators(),
            'missionTypes' => Folder::MISSION_TYPES,
        ]);
    }

    /**
     * Le formulaire est une modale de la fiche (vue cabinet.folder.edit).
     * L'équipe et le responsable ne changent que si le collaborateur a le droit d'affecter.
     * Le statut n'est pas modifié ici : il suivra le parcours du dossier, à définir.
     */
    public function update(UpdateFolderRequest $request, Folder $folder): RedirectResponse
    {
        $author = $request->user();

        $folder->fill(Arr::except($request->validated(), ['manager_id', 'assignee_ids']));
        $folder->updated_by = $author->id;

        $previousMemberIds = $folder->assignees()->pluck('users.id');
        $previousManagerId = $folder->managers()->value('users.id');

        DB::transaction(function () use ($request, $folder, $author): void {
            $folder->save();

            if ($request->canAssignTeam()) {
                $folder->syncTeam($request->validated('assignee_ids', []), $request->validated('manager_id'), $author);
            }
        });

        if ($request->canAssignTeam()) {
            $this->notifyTeamChanges($folder, $previousMemberIds, $previousManagerId, $author);
        }

        if (! $folder->isVisibleTo($author)) {
            return to_route('cabinet.folders.index')
                ->with('status', "Le dossier {$folder->reference} a été mis à jour. Vous n'en faites plus partie.");
        }

        return back()->with('status', "Le dossier {$folder->reference} a été mis à jour.");
    }

    /**
     * Suppression logique : le dossier et son historique sont conservés en base.
     */
    public function destroy(Folder $folder): RedirectResponse
    {
        Gate::authorize('delete', $folder);

        $folder->delete();

        return to_route('cabinet.folders.index')
            ->with('status', "Le dossier {$folder->reference} a été supprimé.");
    }

    /**
     * Prévient les collaborateurs qui viennent d'être désignés responsables ou d'entrer dans l'équipe du dossier.
     *
     * @param  Collection<int, string>  $previousMemberIds
     */
    private function notifyTeamChanges(Folder $folder, Collection $previousMemberIds, ?string $previousManagerId, User $author): void
    {
        $managerId = $folder->managers()->value('users.id');
        $folderLabel = "{$folder->reference} — {$folder->title}";
        $url = route('cabinet.folders.show', $folder);

        foreach ($folder->assignees()->get() as $member) {
            if ($member->getKey() === $managerId && $managerId !== $previousManagerId) {
                ActivityNotification::sendTo([$member], "{$author->full_name} vous a désigné(e) responsable du dossier {$folderLabel}.", $url, $author);
            } elseif (! $previousMemberIds->contains($member->getKey())) {
                ActivityNotification::sendTo([$member], "{$author->full_name} vous a ajouté(e) à l'équipe du dossier {$folderLabel}.", $url, $author);
            }
        }
    }

    /**
     * @return Collection<int, Status>
     */
    private function folderStatuses(): Collection
    {
        return once(fn () => Status::ofType(StatusType::FOLDER)->get());
    }

    /**
     * @return Collection<string, string>
     */
    private function priorities(): Collection
    {
        return Priority::query()->orderBy('level')->pluck('label', 'id');
    }

    /**
     * Collaborateurs qui peuvent faire partie de l'équipe ou être responsables d'un dossier :
     * tous les membres actifs du cabinet, avec leur rôle pour les distinguer (« Awa Koné — Comptable »).
     *
     * @return Collection<string, string>
     */
    private function collaborators(): Collection
    {
        return User::query()
            ->active()
            ->with('roles')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn (User $user): array => [
                $user->id => $user->role_label ? "{$user->full_name} — {$user->role_label}" : $user->full_name,
            ]);
    }
}

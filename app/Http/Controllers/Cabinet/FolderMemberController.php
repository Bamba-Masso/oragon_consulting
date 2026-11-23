<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Personnes en charge d'un dossier (équipe), gérées depuis sa fiche. Exige le droit d'affecter (FolderPolicy::assign).
 */
class FolderMemberController extends Controller
{
    /**
     * Ajoute un collaborateur actif à l'équipe du dossier.
     * Le formulaire est une modale de la fiche du dossier (vue cabinet.folder.partials.member-add).
     */
    public function store(Request $request, Folder $folder): RedirectResponse
    {
        Gate::authorize('assign', $folder);

        $validated = $request->validateWithBag('addMember', [
            'user_id' => [
                'required',
                'uuid',
                Rule::exists(User::class, 'id')->where('is_active', true)->whereNull('deleted_at'),
                Rule::notIn($folder->assignees()->pluck('users.id')->all()),
            ],
        ], [
            'user_id.required' => 'Choisissez un collaborateur.',
            'user_id.uuid' => 'Choisissez un collaborateur dans la liste.',
            'user_id.exists' => 'Choisissez un collaborateur dans la liste.',
            'user_id.not_in' => 'Ce collaborateur fait déjà partie du dossier.',
        ]);

        $member = User::findOrFail($validated['user_id']);
        $folder->assignees()->attach($member, ['assigned_by' => $request->user()->id]);

        return to_route('cabinet.folders.show', $folder)
            ->with('status', "{$member->full_name} a été ajouté(e) aux personnes en charge du dossier.");
    }

    /**
     * Retire un collaborateur de l'équipe. Ses tâches du dossier repassent « non assignées »,
     * sinon elles continueraient à lui ouvrir le dossier.
     */
    public function destroy(Request $request, Folder $folder, User $user): RedirectResponse
    {
        Gate::authorize('assign', $folder);

        abort_unless($folder->assignees()->whereKey($user->id)->exists(), 404);

        DB::transaction(function () use ($folder, $user): void {
            $folder->assignees()->detach($user);
            $folder->tasks()->where('assigned_to', $user->id)->update(['assigned_to' => null]);
        });

        $message = "{$user->full_name} a été retiré(e) des personnes en charge du dossier.";

        if (! $folder->isVisibleTo($request->user())) {
            return to_route('cabinet.folders.index')->with('status', $message);
        }

        return to_route('cabinet.folders.show', $folder)->with('status', $message);
    }
}

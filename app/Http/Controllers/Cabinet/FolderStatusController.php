<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Parcours d'un dossier : En attente → En cours ⇄ Suspendu → Terminé.
 * Réservé au responsable du dossier et à l'administrateur ; chaque changement est inscrit dans l'historique.
 */
class FolderStatusController extends Controller
{
    public function start(Request $request, Folder $folder): RedirectResponse
    {
        Gate::authorize('start', $folder);

        $this->moveTo($folder, Folder::IN_PROGRESS, $request->user(), 'Démarrage du dossier');

        return back()->with('status', "Le dossier {$folder->reference} est en cours.");
    }

    public function suspend(Request $request, Folder $folder): RedirectResponse
    {
        Gate::authorize('suspend', $folder);

        $this->moveTo($folder, Folder::SUSPENDED, $request->user(), 'Dossier suspendu');

        return back()->with('status', "Le dossier {$folder->reference} a été suspendu.");
    }

    public function resume(Request $request, Folder $folder): RedirectResponse
    {
        Gate::authorize('resume', $folder);

        $this->moveTo($folder, Folder::IN_PROGRESS, $request->user(), 'Reprise du dossier');

        return back()->with('status', "Le dossier {$folder->reference} a repris.");
    }

    /**
     * Clôture définitive : le dossier reste consultable, mais plus aucune action n'est possible.
     */
    public function close(Request $request, Folder $folder): RedirectResponse
    {
        Gate::authorize('close', $folder);

        $this->moveTo($folder, Folder::CLOSED, $request->user(), 'Dossier terminé');

        return back()->with('status', "Le dossier {$folder->reference} est terminé.");
    }

    private function moveTo(Folder $folder, string $statusCode, User $author, string $comment): void
    {
        DB::transaction(function () use ($folder, $statusCode, $author, $comment): void {
            $previousStatusId = $folder->status_id;

            $folder->status_id = Folder::statusId($statusCode);
            $folder->updated_by = $author->id;

            if ($statusCode === Folder::CLOSED) {
                $folder->closed_at = now();
            }

            $folder->save();

            $folder->statusChanges()->create([
                'from_status_id' => $previousStatusId,
                'to_status_id' => $folder->status_id,
                'changed_by' => $author->id,
                'comment' => $comment,
            ]);
        });
    }
}

<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\Folder;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tableau de bord du collaborateur : chiffres clés de son périmètre, ses prochaines tâches
 * et les dossiers dont l'échéance approche. Chaque bloc n'est calculé que si le rôle donne accès à la page correspondante.
 */
class DashboardController extends Controller
{
    private const LIST_LIMIT = 5;

    /**
     * Un dossier est « à échéance proche » s'il doit être rendu dans ce nombre de jours (ou s'il est en retard).
     */
    private const UPCOMING_DAYS = 14;

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $activeFoldersCount = null;
        $overdueFoldersCount = null;
        $upcomingFolders = null;

        if ($user->can('view-folders')) {
            $activeFolders = Folder::query()->visibleTo($user)->whereNull('closed_at');

            $activeFoldersCount = (clone $activeFolders)->count();
            $overdueFoldersCount = (clone $activeFolders)->whereDate('due_date', '<', today())->count();
            $upcomingFolders = $activeFolders
                ->whereDate('due_date', '<=', today()->addDays(self::UPCOMING_DAYS))
                ->with(['organization', 'status'])
                ->orderBy('due_date')
                ->take(self::LIST_LIMIT)
                ->get();
        }

        $openTasksCount = null;
        $overdueTasksCount = null;
        $nextTasks = null;

        if ($user->can('view-tasks')) {
            $openTasks = Task::query()
                ->where('assigned_to', $user->id)
                ->whereHas('folder')
                ->whereIn('status_id', [Task::statusId(Task::PENDING), Task::statusId(Task::IN_PROGRESS)]);

            $openTasksCount = (clone $openTasks)->count();
            $overdueTasksCount = (clone $openTasks)->whereDate('due_date', '<', today())->count();
            $nextTasks = $openTasks
                ->with(['folder', 'status'])
                ->orderByRaw('due_date is null')
                ->orderBy('due_date')
                ->take(self::LIST_LIMIT)
                ->get();
        }

        $pendingRequestsCount = null;
        $receivedRequestsCount = null;

        if ($user->can('view-document-requests')) {
            $pendingRequestsCount = DocumentRequest::query()->visibleTo($user)
                ->where('status_id', DocumentRequest::statusId(DocumentRequest::PENDING))
                ->count();
            $receivedRequestsCount = DocumentRequest::query()->visibleTo($user)
                ->where('status_id', DocumentRequest::statusId(DocumentRequest::RECEIVED))
                ->count();
        }

        $documentsToSendCount = $user->can('view-documents')
            ? Document::query()->visibleTo($user)->where('is_published_to_client', false)->count()
            : null;

        return view('cabinet.dashboard', [
            'user' => $user,
            'activeFoldersCount' => $activeFoldersCount,
            'overdueFoldersCount' => $overdueFoldersCount,
            'upcomingFolders' => $upcomingFolders,
            'upcomingDays' => self::UPCOMING_DAYS,
            'openTasksCount' => $openTasksCount,
            'overdueTasksCount' => $overdueTasksCount,
            'nextTasks' => $nextTasks,
            'pendingRequestsCount' => $pendingRequestsCount,
            'receivedRequestsCount' => $receivedRequestsCount,
            'documentsToSendCount' => $documentsToSendCount,
        ]);
    }
}

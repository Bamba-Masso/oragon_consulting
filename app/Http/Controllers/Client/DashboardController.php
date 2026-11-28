<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tableau de bord de l'entreprise : chiffres clés, pièces à fournir et derniers documents reçus.
 * Chaque bloc n'est calculé que si le rôle donne accès à la page correspondante.
 */
class DashboardController extends Controller
{
    private const LIST_LIMIT = 5;

    private const RECENT_DOCUMENTS_DAYS = 30;

    public function __invoke(Request $request): View
    {
        $clientUser = $request->user();

        $activeFoldersCount = $clientUser->can('view-folders')
            ? Folder::query()->visibleTo($clientUser)->whereNull('closed_at')->count()
            : null;

        $openRequests = null;
        $openRequestsCount = null;

        if ($clientUser->can('view-document-requests')) {
            $openRequestsQuery = DocumentRequest::query()
                ->visibleTo($clientUser)
                ->whereIn('status_id', [
                    DocumentRequest::statusId(DocumentRequest::PENDING),
                    DocumentRequest::statusId(DocumentRequest::RECEIVED),
                ]);

            $openRequestsCount = (clone $openRequestsQuery)->count();
            $openRequests = $openRequestsQuery->with(['folder', 'status'])->latest()->take(self::LIST_LIMIT)->get();
        }

        $latestDocuments = null;
        $documentsCount = null;
        $recentDocumentsCount = null;

        if ($clientUser->can('view-documents')) {
            $documentsQuery = Document::query()->visibleTo($clientUser);

            $documentsCount = (clone $documentsQuery)->count();
            $recentDocumentsCount = (clone $documentsQuery)
                ->where('published_at', '>=', now()->subDays(self::RECENT_DOCUMENTS_DAYS))
                ->count();
            $latestDocuments = $documentsQuery->with('folder')->latest('published_at')->take(self::LIST_LIMIT)->get();
        }

        return view('client.dashboard', [
            'clientUser' => $clientUser,
            'activeFoldersCount' => $activeFoldersCount,
            'openRequestsCount' => $openRequestsCount,
            'openRequests' => $openRequests,
            'documentsCount' => $documentsCount,
            'recentDocumentsCount' => $recentDocumentsCount,
            'recentDocumentsDays' => self::RECENT_DOCUMENTS_DAYS,
            'latestDocuments' => $latestDocuments,
        ]);
    }
}

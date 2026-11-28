<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Dossiers de l'entreprise, côté client : consultation seule.
 * Le client ne voit ni les tâches ni la progression interne : seulement les documents que le cabinet lui a envoyés.
 */
class FolderController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Folder::class);

        $search = $request->string('search')->trim()->toString();

        $folders = Folder::query()
            ->visibleTo($request->user())
            ->with(['status', 'managers'])
            ->withCount(['documents as sent_documents_count' => fn (Builder $query) => $query->publishedToClient()])
            ->when($search !== '', fn (Builder $query) => $query
                ->whereAny(['reference', 'title', 'mission_type', 'period'], 'like', "%{$search}%"))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('client.folder.index', [
            'folders' => $folders,
            'search' => $search,
        ]);
    }

    public function show(Folder $folder): View
    {
        Gate::authorize('view', $folder);

        $folder->load(['status', 'managers']);

        return view('client.folder.show', [
            'folder' => $folder,
            'documents' => $folder->documents()->publishedToClient()->latest('published_at')->get(),
        ]);
    }
}

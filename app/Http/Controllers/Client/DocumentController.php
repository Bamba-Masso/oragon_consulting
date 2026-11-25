<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documents envoyés par le cabinet à l'entreprise : consultation et téléchargement seulement.
 */
class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Document::class);

        $search = $request->string('search')->trim()->toString();

        $documents = Document::query()
            ->visibleTo($request->user())
            ->with('folder')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereAny(['name', 'category', 'period'], 'like', "%{$search}%")
                ->orWhereRelation('folder', 'reference', 'like', "%{$search}%")))
            ->latest('published_at')
            ->paginate(15)
            ->withQueryString();

        return view('client.document.index', [
            'documents' => $documents,
            'search' => $search,
        ]);
    }

    public function download(Document $document): StreamedResponse
    {
        Gate::authorize('download', $document);

        abort_unless(Storage::disk(Document::DISK)->exists($document->file_path), 404);

        return Storage::disk(Document::DISK)->download($document->file_path, $document->original_filename);
    }
}

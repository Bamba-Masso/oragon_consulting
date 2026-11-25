<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Mail\DocumentPublished;
use App\Models\ClientUser;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documents des dossiers, côté cabinet : ajout depuis la fiche du dossier, envoi au client, retrait et suppression.
 */
class DocumentController extends Controller
{
    /**
     * Documents des dossiers suivis par le collaborateur, avec recherche et filtre par entreprise.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Document::class);

        $user = $request->user();
        $search = $request->string('search')->trim()->toString();
        $organizationId = $request->string('organization')->toString();

        $documents = Document::query()
            ->visibleTo($user)
            ->with(['folder', 'organization'])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereAny(['name', 'category', 'period', 'original_filename'], 'like', "%{$search}%")
                ->orWhereRelation('folder', 'reference', 'like', "%{$search}%")
                ->orWhereRelation('organization', 'name', 'like', "%{$search}%")))
            ->when($organizationId !== '', fn (Builder $query) => $query->where('organization_id', $organizationId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('cabinet.document.index', [
            'documents' => $documents,
            'organizations' => Organization::query()
                ->whereHas('documents', fn (Builder $query) => $query->visibleTo($user))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'search' => $search,
            'organizationId' => $organizationId,
        ]);
    }

    /**
     * Le document est d'abord interne au cabinet ; il faut l'envoyer pour que le client le voie.
     */
    public function store(Request $request, Folder $folder): RedirectResponse
    {
        Gate::authorize('create', [Document::class, $folder]);

        $validated = $request->validateWithBag('storeDocument', [
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv,zip'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'period' => ['nullable', 'string', 'max:50'],
        ]);

        $file = $request->file('file');

        $document = new Document([
            'organization_id' => $folder->organization_id,
            'name' => $validated['name'],
            'category' => $validated['category'],
            'period' => $validated['period'] ?? null,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'file_path' => $file->store("documents/{$folder->id}", Document::DISK),
        ]);
        $document->uploader()->associate($request->user());
        $folder->documents()->save($document);

        return to_route('cabinet.folders.show', $folder)
            ->with('status', "Le document « {$document->name} » a été ajouté au dossier.");
    }

    public function download(Document $document): StreamedResponse
    {
        Gate::authorize('download', $document);

        abort_unless(Storage::disk(Document::DISK)->exists($document->file_path), 404);

        return Storage::disk(Document::DISK)->download($document->file_path, $document->original_filename);
    }

    /**
     * Envoi au client : le document apparaît dans son espace et ses utilisateurs actifs reçoivent un e-mail.
     */
    public function publish(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('publish', $document);

        $document->is_published_to_client = true;
        $document->published_at = now();
        $document->published_by = $request->user()->id;
        $document->save();

        ClientUser::query()
            ->active()
            ->where('organization_id', $document->organization_id)
            ->get()
            ->each(fn (ClientUser $clientUser) => Mail::to($clientUser)->send(new DocumentPublished($document, $clientUser)));

        return back()->with('status', "Le document « {$document->name} » a été envoyé au client.");
    }

    /**
     * Retrait d'un document envoyé par erreur : il redevient interne au cabinet.
     */
    public function unpublish(Document $document): RedirectResponse
    {
        Gate::authorize('unpublish', $document);

        $document->is_published_to_client = false;
        $document->published_at = null;
        $document->published_by = null;
        $document->save();

        return back()->with('status', "Le document « {$document->name} » n'est plus visible du client.");
    }

    /**
     * Suppression logique : le document et son fichier sont conservés.
     */
    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $document->delete();

        return back()->with('status', "Le document « {$document->name} » a été supprimé.");
    }
}

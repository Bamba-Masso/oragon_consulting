<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Mail\DocumentRequestSent;
use App\Models\ClientUser;
use App\Models\DocumentRequest;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', DocumentRequest::class);

        $search = $request->string('search')->trim()->toString();

        $documentRequests = DocumentRequest::query()
            ->visibleTo($request->user())
            ->with(['folder.organization', 'status'])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereAny(['reference', 'title'], 'like', "%{$search}%")
                ->orWhereRelation('folder', 'reference', 'like', "%{$search}%")
                ->orWhereRelation('folder.organization', 'name', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('cabinet.document-request.index', [
            'documentRequests' => $documentRequests,
            'search' => $search,
        ]);
    }

    public function store(Request $request, Folder $folder): RedirectResponse
    {
        Gate::authorize('create', [DocumentRequest::class, $folder]);

        $validated = $request->validateWithBag('storeDocumentRequest', [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
        ], [
            'title.required' => 'Saisissez l\'intitulé de la demande.',
            'description.required' => 'Décrivez les pièces demandées.',
            '*.max' => 'Ce champ ne doit pas dépasser :max caractères.',
        ]);

        $documentRequest = new DocumentRequest($validated);
        $documentRequest->reference = DocumentRequest::nextReference();
        $documentRequest->requested_by = $request->user()->id;
        $documentRequest->moveTo(DocumentRequest::PENDING);
        $folder->documentRequests()->save($documentRequest);

        $this->notifyClient($documentRequest);

        return to_route('cabinet.document-requests.show', $documentRequest)
            ->with('status', "La demande {$documentRequest->reference} a été envoyée à l'entreprise.");
    }

    public function show(DocumentRequest $documentRequest): View
    {
        Gate::authorize('view', $documentRequest);

        $documentRequest->load([
            'folder.organization',
            'status',
            'requester',
            'responder',
            'closer',
            'messages' => fn ($query) => $query->with(['sender', 'attachments'])->oldest(),
        ]);

        return view('cabinet.document-request.show', ['documentRequest' => $documentRequest]);
    }

    public function remind(DocumentRequest $documentRequest): RedirectResponse
    {
        Gate::authorize('remind', $documentRequest);

        $documentRequest->update(['last_reminded_at' => now()]);
        $this->notifyClient($documentRequest, isReminder: true);

        return to_route('cabinet.document-requests.show', $documentRequest)
            ->with('status', 'L\'entreprise a été relancée.');
    }

    public function close(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        Gate::authorize('close', $documentRequest);

        $documentRequest->moveTo(DocumentRequest::CLOSED);
        $documentRequest->closed_by = $request->user()->id;
        $documentRequest->closed_at = now();
        $documentRequest->save();

        return to_route('cabinet.document-requests.show', $documentRequest)
            ->with('status', "La demande {$documentRequest->reference} a été clôturée.");
    }

    private function notifyClient(DocumentRequest $documentRequest, bool $isReminder = false): void
    {
        $documentRequest->loadMissing('folder');

        ClientUser::query()
            ->active()
            ->where('organization_id', $documentRequest->folder->organization_id)
            ->get()
            ->each(fn (ClientUser $clientUser) => Mail::to($clientUser)->send(
                new DocumentRequestSent($documentRequest, $clientUser, $isReminder)
            ));

        $documentRequest->notifyClient($isReminder
            ? "Relance : le cabinet attend toujours vos pièces pour la demande « {$documentRequest->title} » ({$documentRequest->reference})."
            : "Le cabinet vous demande des pièces : « {$documentRequest->title} » ({$documentRequest->reference}).");
    }
}

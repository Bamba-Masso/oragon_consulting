<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Demandes de pièces, côté client : l'entreprise marque la demande reçue (la discussion s'ouvre) ou la rejette avec un motif.
 */
class DocumentRequestController extends Controller
{
    /**
     * Toutes les demandes de l'entreprise, y compris rejetées (sans accès à leur détail).
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', DocumentRequest::class);

        $search = $request->string('search')->trim()->toString();

        $documentRequests = DocumentRequest::query()
            ->visibleTo($request->user())
            ->with(['folder', 'status'])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereAny(['reference', 'title'], 'like', "%{$search}%")
                ->orWhereRelation('folder', 'reference', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('client.document-request.index', [
            'documentRequests' => $documentRequests,
            'search' => $search,
        ]);
    }

    public function show(DocumentRequest $documentRequest): View
    {
        Gate::authorize('view', $documentRequest);

        $documentRequest->load([
            'folder',
            'status',
            'requester',
            'responder',
            'messages' => fn ($query) => $query->with(['sender', 'attachments'])->oldest(),
        ]);

        return view('client.document-request.show', ['documentRequest' => $documentRequest]);
    }

    /**
     * L'entreprise prend en charge la demande : la discussion avec le cabinet commence.
     */
    public function accept(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        Gate::authorize('respond', $documentRequest);

        $documentRequest->moveTo(DocumentRequest::RECEIVED);
        $documentRequest->responded_by = $request->user()->id;
        $documentRequest->responded_at = now();
        $documentRequest->save();

        $documentRequest->notifyCabinet(
            "{$request->user()->full_name} a confirmé la réception de la demande {$documentRequest->reference} « {$documentRequest->title} »."
        );

        return to_route('client.document-requests.show', $documentRequest)
            ->with('status', 'La demande est marquée comme reçue. Vous pouvez échanger avec le cabinet et lui transmettre vos pièces.');
    }

    /**
     * Rejet motivé : plus aucune action n'est possible et le détail n'est plus consultable.
     */
    public function reject(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        Gate::authorize('respond', $documentRequest);

        $validated = $request->validateWithBag('rejectDocumentRequest', [
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ], [
            'rejection_reason.required' => 'Indiquez le motif du rejet.',
            'rejection_reason.max' => 'Le motif ne doit pas dépasser :max caractères.',
        ]);

        $documentRequest->moveTo(DocumentRequest::REJECTED);
        $documentRequest->responded_by = $request->user()->id;
        $documentRequest->responded_at = now();
        $documentRequest->rejection_reason = $validated['rejection_reason'];
        $documentRequest->save();

        $documentRequest->notifyCabinet(
            "{$request->user()->full_name} a rejeté la demande {$documentRequest->reference} « {$documentRequest->title} » : "
            .Str::limit($validated['rejection_reason'], 120)
        );

        return to_route('client.document-requests.index')
            ->with('status', "La demande {$documentRequest->reference} a été rejetée.");
    }
}

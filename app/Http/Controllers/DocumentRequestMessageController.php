<?php

namespace App\Http\Controllers;

use App\Models\ClientUser;
use App\Models\DocumentMessageAttachment;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Discussion d'une demande de pièces, commune au cabinet et au client :
 * messages avec pièces jointes (stockées sur le disque privé) et téléchargement des fichiers.
 */
class DocumentRequestMessageController extends Controller
{
    /**
     * Disque privé : les fichiers ne sont jamais servis directement, seulement via download().
     */
    private const DISK = 'local';

    public function store(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        Gate::authorize('sendMessage', $documentRequest);

        $validated = $request->validateWithBag('storeMessage', [
            'message' => ['nullable', 'required_without:attachments', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv,zip'],
        ], [
            'message.required_without' => 'Écrivez un message ou joignez au moins un fichier.',
        ]);

        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $documentRequest, $validated, &$storedPaths): void {
                $message = new DocumentRequestMessage(['message' => $validated['message'] ?? null]);
                $message->sender()->associate($request->user());
                $documentRequest->messages()->save($message);

                /** @var UploadedFile $file */
                foreach ($request->file('attachments', []) as $file) {
                    $path = $file->store("document-requests/{$documentRequest->id}", self::DISK);
                    $storedPaths[] = $path;

                    $message->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk(self::DISK)->delete($storedPaths);

            throw $exception;
        }

        return to_route($this->showRoute($request), $documentRequest)
            ->with('status', 'Votre message a été envoyé.');
    }

    /**
     * Le fichier appartient forcément à la demande (liaison de route imbriquée).
     */
    public function download(DocumentRequest $documentRequest, DocumentMessageAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $documentRequest);

        abort_unless(Storage::disk(self::DISK)->exists($attachment->file_path), 404);

        return Storage::disk(self::DISK)->download($attachment->file_path, $attachment->file_name);
    }

    private function showRoute(Request $request): string
    {
        return $request->user() instanceof ClientUser
            ? 'client.document-requests.show'
            : 'cabinet.document-requests.show';
    }
}

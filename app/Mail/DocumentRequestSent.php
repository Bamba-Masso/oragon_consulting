<?php

namespace App\Mail;

use App\Models\ClientUser;
use App\Models\DocumentRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail envoyé aux utilisateurs de l'entreprise quand le cabinet leur demande des pièces,
 * puis à chaque relance tant qu'ils n'ont pas répondu.
 */
class DocumentRequestSent extends Mailable
{
    public function __construct(
        public DocumentRequest $documentRequest,
        public ClientUser $clientUser,
        public bool $isReminder = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isReminder ? 'Relance : pièces demandées' : 'Pièces demandées';

        return new Envelope(
            subject: "{$subject} — {$this->documentRequest->title} ({$this->documentRequest->reference})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.document-request',
            with: [
                'account' => $this->clientUser,
                'documentRequest' => $this->documentRequest,
                'isReminder' => $this->isReminder,
                'requestUrl' => route('client.document-requests.show', $this->documentRequest),
            ],
        );
    }
}

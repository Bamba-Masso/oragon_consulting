<?php

namespace App\Mail;

use App\Models\ClientUser;
use App\Models\Document;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail envoyé aux utilisateurs de l'entreprise quand le cabinet leur envoie un document.
 * Le fichier n'est pas joint : il se télécharge depuis l'espace client, après connexion.
 */
class DocumentPublished extends Mailable
{
    public function __construct(
        public Document $document,
        public ClientUser $clientUser,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nouveau document disponible — {$this->document->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.document-published',
            with: [
                'account' => $this->clientUser,
                'document' => $this->document,
                'documentsUrl' => route('client.documents.index'),
            ],
        );
    }
}

<?php

namespace App\Mail;

use App\Models\ClientUser;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail envoyé quand les identifiants de connexion d'un utilisateur du portail changent
 * (nom d'utilisateur recalculé après un changement de nom, ou nouvelle adresse e-mail).
 * Le mot de passe, lui, ne change pas.
 */
class ClientAccessUpdated extends Mailable
{
    public function __construct(
        public ClientUser $clientUser,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vos accès à l\'espace client ont été mis à jour — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.account-access-updated',
            with: [
                'account' => $this->clientUser,
                'spaceLabel' => 'Espace client',
                'loginUrl' => route('client.login'),
            ],
        );
    }
}

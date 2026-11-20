<?php

namespace App\Mail;

use App\Models\ClientUser;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail d'ouverture de l'espace client : identifiant et mot de passe temporaire.
 *
 * Volontairement non mis en file d'attente (pas de ShouldQueue) : le mot de passe en clair
 * serait sinon sérialisé et stocké dans la table des jobs.
 */
class ClientAccessCredentials extends Mailable
{
    public function __construct(
        public ClientUser $clientUser,
        public string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vos accès à l\'espace client — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.client-access',
            with: [
                'organizationName' => $this->clientUser->organization?->name,
                'loginUrl' => route('client.login'),
            ],
        );
    }
}
